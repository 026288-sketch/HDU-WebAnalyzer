<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Service for working with embeddings and duplicate detection.
 *
 * Communicates with a FastAPI embedding service for:
 * - Checking if a text/article is duplicate
 * - Deleting embeddings by parent or chroma ID
 * - Retrieving parent IDs for chunks
 */
class EmbeddingService
{
    /** @var string API endpoint for duplicate check */
    protected string $apiUrl;

    /** @var string API endpoint for batch deletion */
    protected string $deleteUrl;

    /** @var float|null Threshold for duplicate detection (fetched lazily from FastAPI) */
    protected ?float $threshold = null;

    /**
     * EmbeddingService constructor.
     *
     * Initializes API URLs from configuration.
     */
    public function __construct()
    {
        $this->apiUrl = config('services.embedding.url', 'http://127.0.0.1:8000/check');
        $this->deleteUrl = config('services.embedding.delete_url', 'http://127.0.0.1:8000/delete_batch');
    }

    /**
     * Get the threshold for duplicate detection.
     * Lazy-loads from FastAPI on first call.
     */
    public function getThreshold(): float
    {
        if ($this->threshold === null) {
            $this->threshold = $this->fetchThresholdFromFastApi();
        }

        return $this->threshold;
    }

    /**
     * Fetch threshold value from FastAPI /config endpoint.
     */
    protected function fetchThresholdFromFastApi(): float
    {
        try {
            $response = Http::timeout(3)->get(config('services.embedding.fastapi_url'));

            if ($response->successful()) {
                $threshold = (float) ($response->json('threshold') ?? -1);
                if ($threshold > 0 && $threshold <= 1) {
                    return $threshold;
                }
            }

            return -1;
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Check if a text/article is duplicate based on embeddings.
     *
     * @param  string  $content  Full article text
     * @param  string|null  $summary  Optional summary for faster checking
     * @return array Result with 'duplicate' key and potential error info
     */
    public function checkDuplicate(string $content, ?string $summary = null): array
    {
        try {
            $payload = ['content' => $content];
            if ($summary && strlen(trim($summary)) > 50) {
                $payload['summary'] = $summary;
            }

            $response = Http::timeout(30)->asJson()->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['is_duplicate'])) {
                    $result['duplicate'] = (bool) $result['is_duplicate'];
                    unset($result['is_duplicate']);
                } elseif (! isset($result['duplicate'])) {
                    $result['duplicate'] = false;
                } else {
                    $result['duplicate'] = (bool) $result['duplicate'];
                }

                return $result;
            }

            return [
                'error' => true,
                'message' => 'Embedding service error: '.$response->status(),
                'duplicate' => false,
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Embedding service not available: '.$e->getMessage(),
                'duplicate' => false,
            ];
        }
    }

    /**
     * Delete an embedding by its Chroma ID (parent ID).
     * Removes all article chunks with this parent_id.
     */
    public function deleteByChromaId(string $chromaId): bool
    {
        try {
            $response = Http::timeout(30)->asJson()->post($this->deleteUrl, [
                'parent_ids' => [$chromaId],
            ]);

            if ($response->successful()) {
                return isset($response->json()['status']) && $response->json()['status'] === 'ok';
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete all chunks of an article by parent ID.
     */
    public function deleteByParentId(string $parentId): bool
    {
        try {
            $response = Http::timeout(30)->asJson()->post($this->deleteUrl, [
                'parent_ids' => [$parentId],
            ]);

            if ($response->successful()) {
                return isset($response->json()['status']) && $response->json()['status'] === 'ok';
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retrieve parent IDs for given chunk IDs from ChromaDB.
     */
    public function getParentIds(array $chunkIds): array
    {
        try {
            // Use base URL for API
            $apiBase = config('services.embedding.base_url', 'http://127.0.0.1:8000');
            $response = Http::timeout(10)->asJson()->post("$apiBase/get_parent_ids", [
                'chunk_ids' => $chunkIds,
            ]);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
