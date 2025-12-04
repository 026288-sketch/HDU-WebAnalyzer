<?php

namespace App\Http\Controllers;

use App\Helpers\ChromaIdHelper;
use App\Models\Node;
use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

/**
 * Class ChromaController
 *
 * Manages interactions with ChromaDB microservice:
 * listing embeddings, checking content, and bulk deletion.
 */
class ChromaController extends Controller
{
    private string $apiBase;

    private EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        // CHANGED: Use the full service URL from environment variables.
        // In Docker, this defaults to "http://similarity:8000".
        $this->apiBase = env('SIMILARITY_URL', 'http://similarity:8000');

        $this->embeddingService = $embeddingService;
    }

    /**
     * Display a paginated list of embeddings, grouped by parent_id.
     */
    public function index(Request $request)
    {
        try {
            // Check connection to the Python service
            $response = Http::timeout(5)->get("{$this->apiBase}/debug");

            if (! $response->successful()) {
                return view('chroma.index', [
                    'data' => ['documents' => []],
                    'paginatedItems' => new LengthAwarePaginator([], 0, 20, 1),
                    'totalArticles' => 0,
                    'totalChunks' => 0,
                    'error' => 'Failed to connect to embedding service (Python)',
                ]);
            }

            $data = $response->json();

            if (isset($data['error']) || isset($data['detail'])) {
                $data = ['documents' => []];
            }

            $groupedByParent = $this->groupDocumentsByParent($data['documents'] ?? []);
            $paginatedItems = $this->paginateGroups($groupedByParent, $request);

            $totalArticles = count($groupedByParent);
            $totalChunks = array_sum(array_column($groupedByParent, 'total_chunks'));

            return view('chroma.index', compact('data', 'paginatedItems', 'totalArticles', 'totalChunks'));

        } catch (\Exception $e) {
            return view('chroma.index', [
                'data' => ['documents' => []],
                'paginatedItems' => new LengthAwarePaginator([], 0, 20, 1),
                'totalArticles' => 0,
                'totalChunks' => 0,
                'error' => 'Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Group documents by parent_id with additional metadata.
     */
    private function groupDocumentsByParent(array $documents): array
    {
        $groupedByParent = [];

        foreach ($documents as $doc) {
            $parentId = $doc['metadata']['parent_id'] ?? $doc['id'] ?? 'unknown';

            if (! isset($groupedByParent[$parentId])) {
                $groupedByParent[$parentId] = [
                    'parent_id' => $parentId,
                    'node_id' => ChromaIdHelper::findOriginalNodeId($parentId),
                    'chunks' => [],
                    'total_chunks' => 0,
                    'first_chunk_preview' => null,
                ];
            }

            $groupedByParent[$parentId]['chunks'][] = $doc;
            $groupedByParent[$parentId]['total_chunks']++;

            if ($groupedByParent[$parentId]['first_chunk_preview'] === null) {
                $groupedByParent[$parentId]['first_chunk_preview'] = $doc['document_preview'] ?? '';
            }
        }

        return array_values($groupedByParent);
    }

    /**
     * Paginate grouped documents.
     */
    private function paginateGroups(array $groups, Request $request): LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 20);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $items = collect($groups);
        $currentPageItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Check content in ChromaDB without saving (Dry Run).
     */
    public function check(Request $request)
    {
        $content = $request->input('content');

        if (empty($content)) {
            return back()->with('error', 'Content cannot be empty');
        }

        try {
            $response = Http::timeout(30)->post("{$this->apiBase}/check_only", [
                'content' => $content,
            ]);

            if (! $response->successful()) {
                return back()->with('error', 'Failed to check content via Python service');
            }

            return back()->with('result', $response->json());

        } catch (\Exception $e) {
            return back()->with('error', 'Error checking content: '.$e->getMessage());
        }
    }

    /**
     * Bulk delete embeddings by chunk IDs with validation and database cleanup.
     */
    public function delete(Request $request)
    {
        $chunkIds = $request->input('ids', []);

        if (empty($chunkIds)) {
            return back()->with('error', 'No IDs selected');
        }

        try {
            // Validate and filter ChromaIDs
            $validChunkIds = ChromaIdHelper::filterValid($chunkIds);
            $invalidChunkIds = ChromaIdHelper::getInvalid($chunkIds);

            if (empty($validChunkIds)) {
                return back()->with('error', 'No valid ChromaDB IDs provided');
            }

            // Extract unique base IDs
            $parentIds = ChromaIdHelper::extractUniqueBaseIds($validChunkIds);

            if (empty($parentIds)) {
                return back()->with('error', 'Failed to extract parent IDs');
            }

            // Delete from ChromaDB (Python Service)
            $this->deleteFromChromaDB($parentIds);

            // Delete nodes from local MySQL database
            $deletedCount = $this->deleteNodesFromDatabase($parentIds);

            // Build response message
            $message = $deletedCount > 0
                ? "Successfully deleted {$deletedCount} article(s) with all their chunks."
                : 'Chunks deleted from ChromaDB, but no matching articles found in database.';

            if (! empty($invalidChunkIds)) {
                $message .= ' '.count($invalidChunkIds).' invalid ID(s) were skipped.';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error during deletion: '.$e->getMessage());
        }
    }

    /**
     * Delete embeddings from ChromaDB by parent IDs via Python API.
     */
    private function deleteFromChromaDB(array $parentIds): void
    {
        try {
            $response = Http::timeout(30)->post("{$this->apiBase}/delete_batch", [
                'parent_ids' => $parentIds,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Python service returned error: " . $response->body());
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete from ChromaDB: {$e->getMessage()}");
        }
    }

    /**
     * Delete nodes from database by parent IDs.
     */
    private function deleteNodesFromDatabase(array $parentIds): int
    {
        $deletedCount = 0;

        foreach ($parentIds as $parentId) {
            $nodeId = ChromaIdHelper::findOriginalNodeId($parentId);

            if (! $nodeId) {
                continue;
            }

            $node = Node::find($nodeId);
            if ($node) {
                $node->delete();
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
