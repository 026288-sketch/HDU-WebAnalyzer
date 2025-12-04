<?php

namespace App\Services;

use App\Models\Node;
use App\Models\NodeSentiment;
use App\Services\Logs\LoggerService;
use Exception;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Part;
use Gemini\Enums\ResponseMimeType;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Service for analyzing nodes (articles) for sentiment and emotion.
 *
 * Uses Gemini AI models to detect sentiment ("positive", "negative", "neutral")
 * and emotion ("anger", "sadness", "disgust", "fear", "joy", "surprise", "neutral").
 */
class AnalysisService
{
    protected string $model;

    protected $client;

    protected LoggerService $logger;

    /**
     * AnalysisService constructor.
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Main method: analyze a Node's text content.
     *
     * Uses summary or falls back to first 200 words of content.
     * Stores sentiment and emotion in NodeSentiment and marks Node as analyzed.
     */
    public function analyzeNode(Node $node): void
    {
        // Use summary or fallback to content
        $text = strip_tags($node->summary);
        if (empty($text) || mb_strlen($text) < 50) {
            $content = strip_tags($node->content ?? '');
            $words = preg_split('/\s+/', $content);
            $text = implode(' ', array_slice($words, 0, 200));
        }

        if (empty($text)) {
            $this->logger->log('AnalysisService', 'WARNING', 'No text available for analysis', [
                'node_id' => $node->id,
            ]);

            return;
        }

        try {
            DB::transaction(function () use ($node, $text) {
                [$sentiment, $emotion] = $this->getSentimentAndEmotion($text);

                // Save results
                NodeSentiment::updateOrCreate(
                    ['node_id' => $node->id],
                    ['sentiment' => $sentiment, 'emotion' => $emotion]
                );

                // Mark node as analyzed
                $node->update(['analyzed_at' => now()]);

                $this->logger->log('AnalysisService', 'INFO', "Analysis completed for Node {$node->id}", [
                    'sentiment' => $sentiment,
                    'emotion' => $emotion,
                ]);
            });
        } catch (Throwable $e) {
            // Transaction will rollback automatically
            $this->logger->log('AnalysisService', 'ERROR', "Node analysis failed for Node {$node->id}", [
                'exception' => $e->getMessage(),
                'node_id' => $node->id,
            ]);
            throw $e;
        }
    }

    /**
     * Call Gemini AI models to get sentiment and emotion.
     *
     * Uses primary model "gemini-2.5-flash-lite" and fallback "Gemma-3-12b-IT".
     *
     * @return array [sentiment, emotion]
     *
     * @throws Exception if both models fail or return invalid data
     */
    protected function getSentimentAndEmotion(string $text): array
    {
        $primaryModel = 'gemini-2.5-flash-lite';
        $fallbackModel = 'models/gemma-3-12b-it';

$instruction = <<<'EOT'
You are an assistant that analyzes the sentiment and emotion of Ukrainian news articles.

Analyze the article and return strictly in JSON with Ukrainian field values:
- "sentiment": must be one of ["негативна", "позитивна", "нейтральна"]
- "emotion": must be one of ["гнів", "смуток", "відраза", "страх", "радість", "здивування", "нейтральна"]

Rules:
- Always identify the most appropriate sentiment and emotion
- Use "нейтральна" only if truly unclear
- Return ONLY Ukrainian values in JSON format
- No explanations, no English words in values
- Respond with valid JSON only

Example output:
{
    "sentiment": "негативна",
    "emotion": "гнів"
}
EOT;

        // --- 1. Try Gemini 2.5 Flash Lite ---
        try {
            $systemInstruction = new Content([new Part(text: $instruction)], role: Role::MODEL);
            $contents = new Content([new Part(text: $text)], role: Role::USER);
            $config = new GenerationConfig(
                maxOutputTokens: 512,
                responseMimeType: ResponseMimeType::APPLICATION_JSON
            );

            $response = Gemini::generativeModel(model: $primaryModel)
                ->withSystemInstruction($systemInstruction)
                ->withGenerationConfig($config)
                ->generateContent($contents);

            if ($response->promptFeedback && $response->promptFeedback->blockReason) {
                throw new Exception('Gemini flash-lite prompt blocked: '.$response->promptFeedback->blockReason->name);
            }

            $cleanText = str_replace(['```json', '```'], '', $response->text());
            $parsed = json_decode($cleanText, true);

            if (is_array($parsed) && isset($parsed['sentiment'], $parsed['emotion'])) {
                return [$parsed['sentiment'], $parsed['emotion']];
            }

            throw new Exception("Gemini flash-lite returned invalid JSON: {$cleanText}");
        } catch (Throwable $e) {
            $this->logger->log('AnalysisService', 'ERROR', 'Primary Gemini failed, trying Gemma fallback', [
                'type' => 'ai_service',
                'node_text_preview' => mb_substr($text, 0, 100),
                'exception' => $e->getMessage(),
            ]);
        }

        // --- 2. Fallback to Gemma 3.12b IT ---
        try {
            $prompt = $instruction."\n\nArticle text:\n".$text;

            $response = Gemini::generativeModel(model: $fallbackModel)
                ->generateContent($prompt);

            $cleanText = str_replace(['```json', '```'], '', $response->text());
            $parsed = json_decode($cleanText, true);

            if (is_array($parsed) && isset($parsed['sentiment'], $parsed['emotion'])) {
                return [$parsed['sentiment'], $parsed['emotion']];
            }

            throw new Exception("Gemma fallback returned invalid JSON: {$cleanText}");
        } catch (Throwable $e) {
            $this->logger->log('AnalysisService', 'ERROR', 'Fallback Gemma failed', [
                'type' => 'ai_service',
                'node_text_preview' => mb_substr($text, 0, 100),
                'exception' => $e->getMessage(),
            ]);

            throw new Exception('Failed to obtain sentiment and emotion from both models.');
        }
    }
}
