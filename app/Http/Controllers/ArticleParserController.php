<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\NodeEmbedding;
use App\Models\NodeLink;
use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use App\Services\Parser\ArticleContentParser;
use App\Services\Parser\HashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class ArticleParserController
 *
 * Handles the parsing of articles from given URLs, storing them in the database,
 * checking for duplicates using the embedding service, and logging all operations.
 */
class ArticleParserController extends Controller
{
    /**
     * Logger service for logging actions and errors.
     */
    protected LoggerService $logger;

    /**
     * Embedding service for checking duplicate content.
     */
    protected EmbeddingService $embeddingService;

    /**
     * Constructor with dependency injection.
     */
    public function __construct(LoggerService $logger, EmbeddingService $embeddingService)
    {
        $this->logger = $logger;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Parse articles from given URLs.
     *
     * @param  Request  $request  HTTP request containing 'urls' array
     * @param  ArticleContentParser  $parser  Parser service for extracting article content
     * @return \Illuminate\View\View
     */
    public function parse(Request $request, ArticleContentParser $parser)
    {
        $urls = $request->input('urls', []);
        $results = [];
        $savedCount = 0;

        foreach ($urls as $url) {
            try {
                $this->logger->log('ArticleParserController', 'INFO', 'Processing article', ['url' => $url]);

                // Parse article content
                $parsed = $parser->parseHtml($url);

                if (! $parsed) {
                    throw new \RuntimeException('Parser returned empty content');
                }

                // Normalize text fields
                $parsed['title'] = html_entity_decode($parsed['title'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed['content'] = html_entity_decode($parsed['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed['summary'] = html_entity_decode($parsed['summary'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // Generate hash for content deduplication
                $hash = HashService::generate($parsed['title'], $parsed['content']);
                $parsed['hash'] = $hash;
                $parsed['url'] = $url;

                // Check for duplicates using embedding service
                $check = $this->embeddingService->checkDuplicate($parsed['content']);

                if (! empty($check['error']) && $check['error'] === true) {
                    $this->logger->log('ArticleParserController', 'ERROR', 'Embedding service error', [
                        'url' => $url,
                        'message' => $check['message'],
                    ]);
                } elseif (! empty($check['duplicate']) && $check['duplicate'] === true) {
                    $this->logger->log('ArticleParserController', 'INFO', 'Duplicate article skipped (embedding)', [
                        'url' => $url,
                        'chroma_id' => $check['chroma_id'] ?? null,
                        'similarity' => $check['similarity'] ?? null,
                    ]);

                    // Update or create a link entry as duplicate
                    NodeLink::updateOrCreate(
                        ['url' => $url],
                        [
                            'parsed' => 1,
                            'last_error' => 'duplicate',
                            'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                            'source' => parse_url($url, PHP_URL_HOST) ?? 'unknown',
                        ]
                    );

                    continue; // Skip saving duplicate content
                }

                // Save article and related data in a transaction
                DB::transaction(function () use ($parsed, $check, &$results, &$savedCount, $url) {
                    $node = Node::firstOrCreate(
                        ['hash' => $parsed['hash']],
                        [
                            'title' => $parsed['title'],
                            'content' => $parsed['content'],
                            'summary' => $parsed['summary'] ?? null,
                            'url' => $url,
                            'timestamp' => $parsed['timestamp'] ?? now(),
                            'image' => $parsed['image'] ?? null,
                        ]
                    );

                    // Update or create link record
                    NodeLink::updateOrCreate(
                        ['url' => $url],
                        [
                            'parsed' => 1,
                            'last_error' => null,
                            'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                            'source' => parse_url($url, PHP_URL_HOST) ?? 'unknown',
                        ]
                    );

                    // Save embedding relationship
                    $chromaId = $check['parent_id'] ?? $check['chroma_id'] ?? null;
                    if (! empty($chromaId)) {
                        NodeEmbedding::firstOrCreate(
                            ['node_id' => $node->id],
                            [
                                'chroma_id' => $chromaId,
                                'similarity' => isset($check['similarity']) ? (float) $check['similarity'] : null,
                            ]
                        );
                    }

                    $savedCount++;

                    $results[] = [
                        'url' => $url,
                        'title' => $parsed['title'],
                        'content' => $parsed['content'],
                        'summary' => $parsed['summary'] ?? null,
                        'image' => $parsed['image'] ?? null,
                        'timestamp' => $parsed['timestamp'] ?? now(),
                        'chroma_id' => $check['chroma_id'] ?? null,
                        'similarity' => $check['similarity'] ?? null,
                    ];
                });

            } catch (\Throwable $e) {
                $errorText = get_class($e).': '.$e->getMessage();

                // Update or create link record on error
                NodeLink::updateOrCreate(
                    ['url' => $url],
                    [
                        'parsed' => 0,
                        'last_error' => $errorText,
                        'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                        'source' => parse_url($url, PHP_URL_HOST) ?? 'unknown',
                    ]
                );

                $this->logger->log('ArticleParserController', 'ERROR', 'Exception while processing article', [
                    'url' => $url,
                    'exception' => $errorText,
                ]);

                \Log::error('ArticleParserController Exception', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->logger->log('ArticleParserController', 'INFO', 'All articles processed.', [
            'Added new articles' => $savedCount,
        ]);

        // Return results to view
        return view('parser.contents', [
            'articles' => $results,
            'savedCount' => $savedCount,
        ]);
    }
}
