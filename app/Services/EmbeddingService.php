<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Service for working with embeddings and duplicate detection.
 *
 * Communicates with a FastAPI embedding service for:
 * - Checking if a text/article is duplicate
 * - Deleting embeddings by parent or chroma ID
 * - Retrieving configuration
 */
class EmbeddingService
{
    protected string $apiUrl;

    protected ?float $threshold = null;

    protected const TIMEOUT = 30;

    public function __construct()
    {
        // Use the SIMILARITY_URL environment variable.
        // In Docker, this defaults to 'http://similarity:8000', which allows
        // connecting to the service by its hostname within the network.
        $this->apiUrl = env('SIMILARITY_URL', 'http://similarity:8000');
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
     * Fetch configuration from FastAPI /config endpoint.
     */
    public function getConfig(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->apiUrl}/config");

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fetch threshold value from FastAPI /config endpoint.
     */
    protected function fetchThresholdFromFastApi(): float
    {
        $config = $this->getConfig();
        $threshold = (float) ($config['threshold'] ?? -1);

        return ($threshold > 0 && $threshold <= 1) ? $threshold : -1;
    }

    /**
     * Check if a text/article is duplicate based on embeddings.
     *
     * @param  string  $content  Full article text
     * @param  string|null  $summary  Optional summary for faster checking
     * @return array Result with 'duplicate' key and metadata
     */
    public function checkDuplicate(string $content, ?string $summary = null): array
    {
        try {
            $payload = ['content' => $content];

            if ($summary && strlen(trim($summary)) > 50) {
                $payload['summary'] = $summary;
            }

            $response = Http::timeout(self::TIMEOUT)->post("{$this->apiUrl}/check", $payload);

            if ($response->successful()) {
                $result = $response->json();

                return $this->normalizeResponse($result);
            }

            return [
                'error' => true,
                'message' => "Embedding service error: {$response->status()}",
                'duplicate' => false,
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => "Embedding service unavailable: {$e->getMessage()}",
                'duplicate' => false,
            ];
        }
    }

    /**
     * Check if duplicate without saving to database.
     *
     * @param  string  $content  Full article text
     * @param  string|null  $summary  Optional summary
     * @return array Result with duplicate status
     */
    public function checkDuplicateOnly(string $content, ?string $summary = null): array
    {
        try {
            $payload = ['content' => $content];

            if ($summary && strlen(trim($summary)) > 50) {
                $payload['summary'] = $summary;
            }

            $response = Http::timeout(self::TIMEOUT)->post("{$this->apiUrl}/check_only", $payload);

            if ($response->successful()) {
                $result = $response->json();

                return $this->normalizeResponse($result);
            }

            return [
                'error' => true,
                'message' => "Embedding service error: {$response->status()}",
                'duplicate' => false,
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => "Embedding service unavailable: {$e->getMessage()}",
                'duplicate' => false,
            ];
        }
    }

    /**
     * Delete an embedding by parent ID.
     * Removes all article chunks with this parent_id.
     */
    public function deleteByParentId(string $parentId): bool
    {
        try {
            $response = Http::timeout(self::TIMEOUT)->post("{$this->apiUrl}/delete_batch", [
                'parent_ids' => [$parentId],
            ]);

            return $response->successful() &&
                   ($response->json()['status'] ?? '') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete multiple embeddings by parent IDs.
     */
    public function deleteByParentIds(array $parentIds): bool
    {
        if (empty($parentIds)) {
            return false;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)->post("{$this->apiUrl}/delete_batch", [
                'parent_ids' => array_unique($parentIds),
            ]);

            return $response->successful() &&
                   ($response->json()['status'] ?? '') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Normalize response from FastAPI to consistent format.
     */
    private function normalizeResponse(array $result): array
    {
        // Convert is_duplicate to duplicate if needed
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
}
