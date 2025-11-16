<?php

namespace App\Services;

use App\Models\Node;
use App\Models\Tag;
use App\Services\Logs\LoggerService;
use Exception;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Part;
use Gemini\Enums\ResponseMimeType;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Service for generating relevant tags for a Node.
 *
 * Uses Gemini AI models to extract up to 3-5 relevant tags from the content.
 */
class TagGeneratorService
{
    /**
     * Gemini model name used for tag generation.
     */
    protected string $model;

    /**
     * Logger service for recording info and errors.
     */
    protected LoggerService $logger;

    /**
     * Constructor.
     */
    public function __construct(LoggerService $logger)
    {
        $this->model = 'gemini-2.5-flash-lite';
        $this->logger = $logger;
    }

    /**
     * Generate tags for a given Node and save them in the database.
     *
     * @throws Throwable
     */
    public function generateTags(Node $node): void
    {
        $text = strip_tags($node->summary ?: $node->content);

        try {
            $tags = $this->requestTags($text);

            DB::transaction(function () use ($node, $tags) {
                $tagIds = [];
                foreach ($tags as $tagName) {
                    $slug = Str::slug($tagName);
                    $tag = Tag::firstOrCreate(
                        ['slug' => $slug],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $node->tags()->syncWithoutDetaching($tagIds);
            });

            $this->logger->log('TagGeneratorService', 'INFO', "✅ Tags generated for Node {$node->id}", [
                'tags' => $tags,
            ]);
        } catch (Throwable $e) {
            // Log only once at the top level
            $this->logger->log('TagGeneratorService', 'ERROR', "❌ Error generating tags for Node {$node->id}", [
                'exception' => $e->getMessage(),
                'node_id' => $node->id,
            ]);
            throw $e;
        }
    }

    /**
     * Request tags from Gemini AI for a given text.
     *
     * @throws Exception
     */
    protected function requestTags(string $text): array
    {
        $systemInstruction = new Content([
            new Part(text: <<<'TXT'
            You are an assistant that generates relevant tags for Ukrainian news articles.
            Analyze the text and suggest up to 3-5 relevant tags in Ukrainian.

            Rules:
            - Return strictly in JSON format like: {"tags": ["tag1","tag2"]}.
            - Each tag should consist of one word, or at most two words (preferably one).
            - Use only nouns or short descriptive phrases, no punctuation or hashtags.
            - Avoid duplicates or very general words (e.g., "news", "Ukraine").
        TXT),
        ], role: Role::MODEL);

        $config = new GenerationConfig(
            maxOutputTokens: 512,
            responseMimeType: ResponseMimeType::APPLICATION_JSON
        );

        $contents = new Content([new Part(text: $text)], role: Role::USER);

        // Use Gemini Laravel Facade directly
        $response = Gemini::generativeModel(model: $this->model)
            ->withSystemInstruction($systemInstruction)
            ->withGenerationConfig($config)
            ->generateContent($contents);

        // Clean possible triple quotes and extra characters
        $cleanText = trim(str_replace(['```json', '```', "\n"], '', $response->text()));

        $data = json_decode($cleanText, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['tags']) || ! is_array($data['tags'])) {
            throw new Exception('Failed to parse JSON: '.json_last_error_msg()." | Response: {$cleanText}");
        }

        // Normalize tags: lowercase and trimmed
        return array_map(fn ($tag) => mb_strtolower(trim($tag)), $data['tags']);
    }
}
