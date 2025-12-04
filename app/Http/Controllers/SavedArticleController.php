<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\NodeEmbedding;
use App\Models\NodeLink;
use App\Models\Tag;
use App\Services\Dashboard\DashboardCounterService;
use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Class SavedArticleController
 *
 * Controller for managing saved articles, including CRUD operations,
 * clearing all articles, and handling embeddings in ChromaDB.
 */
class SavedArticleController extends Controller
{
    protected LoggerService $logger;

    protected EmbeddingService $embeddingService;

    protected DashboardCounterService $dashboardCounters;

    public function __construct(
        LoggerService $logger,
        EmbeddingService $embeddingService,
        DashboardCounterService $dashboardCounters
    ) {
        $this->logger = $logger;
        $this->embeddingService = $embeddingService;
        $this->dashboardCounters = $dashboardCounters;
    }

    /**
     * Display a paginated list of saved articles.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $articles = Node::orderByDesc('timestamp')->paginate(10);

        return view('parser.articles', compact('articles'));
    }

    /**
     * Clear all articles and their embeddings from ChromaDB and reset dashboard counters.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clear()
    {
        // Collect all chroma_ids
        $chromaIds = Node::with('embedding')
            ->get()
            ->pluck('embedding.chroma_id')
            ->filter()
            ->values()
            ->all();

        // Send batch delete request to FastAPI if chroma_ids exist
        if (! empty($chromaIds)) {
            try {
                $apiUrl = env('SIMILARITY_URL', 'http://similarity:8000');

                $response = Http::timeout(30)
                    ->asJson()
                    ->post("{$apiUrl}/delete_batch", [
                        'ids' => array_values($chromaIds),
                    ]);

                if (! $response->successful()) {
                    $this->logger->log(
                        'ChromaService',
                        'ERROR',
                        'Chroma batch delete failed',
                        [
                            'type' => 'embedding',
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->log(
                    'ChromaService',
                    'ERROR',
                    'Chroma batch delete exception: '.$e->getMessage(),
                    ['type' => 'embedding', 'trace' => $e->getTraceAsString()]
                );
            }
        }

        // Delete related records
        NodeEmbedding::query()->delete();
        Node::query()->delete();
        NodeLink::query()->delete();
        Tag::query()->delete();

        // Reset auto-increments
        DB::statement('ALTER TABLE node_embeddings AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE nodes AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE node_links AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE tags AUTO_INCREMENT = 1');

        // Reset dashboard counters related to nodes
        $now = now();
        $zero = json_encode(['count' => 0]);

        $resetKeys = [
            DashboardCounterService::KEY_TOTAL_NODES,
            DashboardCounterService::KEY_NODES_PARSED,
            DashboardCounterService::KEY_NODES_MISSING_CONTENT,
            DashboardCounterService::KEY_NODES_DUPLICATES,
            DashboardCounterService::KEY_SENTIMENT_POSITIVE,
            DashboardCounterService::KEY_SENTIMENT_NEGATIVE,
            DashboardCounterService::KEY_SENTIMENT_NEUTRAL,
            DashboardCounterService::KEY_EMOTION_ANGER,
            DashboardCounterService::KEY_EMOTION_SADNESS,
            DashboardCounterService::KEY_EMOTION_DISGUST,
            DashboardCounterService::KEY_EMOTION_FEAR,
            DashboardCounterService::KEY_EMOTION_JOY,
            DashboardCounterService::KEY_EMOTION_SURPRISE,
            DashboardCounterService::KEY_EMOTION_NEUTRAL,
        ];

        foreach ($resetKeys as $key) {
            DB::table('dashboard_counters')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $zero,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        // Reset error counters
        $errorsDefault = json_encode([
            'count' => 0,
            'types' => [
                'parser' => 0,
                'console' => 0,
                'embedding' => 0,
                'ai_service' => 0,
            ],
            'last_errors' => [],
        ]);

        DB::table('dashboard_counters')->updateOrInsert(
            ['key' => DashboardCounterService::KEY_ERRORS],
            [
                'value' => $errorsDefault,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        // Update aggregate counters for tags and sources
        $this->dashboardCounters->updateTotalTags();
        $this->dashboardCounters->updateTotalSources();

        return redirect()->route('articles.index')
            ->with('success', 'All articles and their embeddings in ChromaDB have been removed.');
    }

    /**
     * Display a single article.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $article = Node::findOrFail($id);

        return view('parser.show', compact('article'));
    }

    /**
     * Display edit form for a single article.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $article = Node::findOrFail($id);

        return view('parser.edit', compact('article'));
    }

    /**
     * Update article data including tags.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $article = Node::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $article->update($validated);

        if (! empty($validated['tags'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags'])));
            $tagIds = [];

            foreach ($tagNames as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }

            $article->tags()->sync($tagIds);
        } else {
            $article->tags()->detach();
        }

        $this->logger->log(
            'SavedArticleController',
            'INFO',
            'Article updated',
            ['id' => $article->id]
        );

        return redirect()->route('articles.show', $article->id)
            ->with('success', 'Article updated successfully!');
    }

    /**
     * Delete a single article and its embedding.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $article = Node::with('embedding')->findOrFail($id);

        // Delete embedding via EmbeddingService
        if ($article->embedding && $article->embedding->chroma_id) {
            $deleted = $this->embeddingService->deleteByChromaId($article->embedding->chroma_id);

            if (! $deleted) {
                $this->logger->log(
                    'ChromaService',
                    'ERROR',
                    'Failed to delete embedding from ChromaDB',
                    ['type' => 'embedding', 'node_id' => $id, 'chroma_id' => $article->embedding->chroma_id]
                );
            }
        }

        // Delete related links by URL
        if ($article->url) {
            NodeLink::where('url', $article->url)->delete();
        }

        // Delete the article (cascades to NodeEmbedding)
        $article->delete();

        $this->logger->log(
            'SavedArticleController',
            'INFO',
            'Article deleted',
            ['id' => $id]
        );

        return redirect()->route('articles.index')
            ->with('success', 'Article deleted successfully!');
    }
}
