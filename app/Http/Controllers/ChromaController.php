<?php

namespace App\Http\Controllers;

use App\Models\NodeEmbedding;
use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

/**
 * Class ChromaController
 *
 * Manages interactions with ChromaDB: listing embeddings, checking content, and bulk deletion.
 */
class ChromaController extends Controller
{
    /**
     * Base URL for ChromaDB API
     */
    private string $apiBase;

    /**
     * Embedding service for handling parent_id mapping and other embedding logic.
     */
    private EmbeddingService $embeddingService;

    /**
     * Constructor with dependency injection.
     */
    public function __construct(EmbeddingService $embeddingService)
    {
        $this->apiBase = 'http://127.0.0.1:8000';
        $this->embeddingService = $embeddingService;
    }

    /**
     * Display a paginated list of embeddings, grouped by parent_id.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $response = Http::get("$this->apiBase/debug");
        $data = $response->json();

        // Handle API error
        if (isset($data['error']) || isset($data['detail'])) {
            $data = ['documents' => []];
        }

        // Group documents by parent_id
        $groupedByParent = [];
        if (! empty($data['documents']) && is_array($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                $parentId = $doc['metadata']['parent_id'] ?? $doc['id'] ?? 'unknown';
                if (! isset($groupedByParent[$parentId])) {
                    $groupedByParent[$parentId] = [
                        'parent_id' => $parentId,
                        'chunks' => [],
                        'total_chunks' => 0,
                        'first_chunk_preview' => null,
                        'created_at' => null,
                    ];
                }
                $groupedByParent[$parentId]['chunks'][] = $doc;
                $groupedByParent[$parentId]['total_chunks']++;

                // Store preview of first chunk
                if ($groupedByParent[$parentId]['first_chunk_preview'] === null) {
                    $groupedByParent[$parentId]['first_chunk_preview'] = $doc['document_preview'] ?? '';
                }
            }
        }

        // Convert grouped array for pagination
        $groupedArray = array_values($groupedByParent);

        $perPage = $request->input('per_page', 20);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $items = collect($groupedArray);
        $currentPageItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedItems = new LengthAwarePaginator(
            $currentPageItems,
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalArticles = count($groupedArray);
        $totalChunks = array_sum(array_column($groupedArray, 'total_chunks'));

        return view('chroma.index', compact('data', 'paginatedItems', 'totalArticles', 'totalChunks'));
    }

    /**
     * Check content in ChromaDB without saving.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function check(Request $request)
    {
        $content = $request->input('content');

        if (empty($content)) {
            return back()->with('error', 'Content is empty');
        }

        $response = Http::post("$this->apiBase/check_only", ['content' => $content]);

        if (! $response->successful()) {
            return back()->with('error', 'Failed to check content')->with('response', $response->body());
        }

        return back()->with('result', $response->json());
    }

    /**
     * Bulk delete embeddings by chunk IDs, also deleting related nodes if needed.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        $chunkIds = $request->input('ids', []);

        if (empty($chunkIds)) {
            return back()->with('error', 'No IDs selected');
        }

        try {
            // Map chunk IDs to parent IDs
            $parentIdMap = $this->embeddingService->getParentIds($chunkIds);
            $notFoundIds = array_diff($chunkIds, array_keys($parentIdMap));
            $foundParentIds = array_values($parentIdMap);

            // Legacy deletion if no parent IDs found
            if (empty($parentIdMap) && empty($notFoundIds)) {
                $response = Http::timeout(30)->asJson()->post("$this->apiBase/delete_batch", [
                    'ids' => $chunkIds,
                ]);

                if (! $response->successful() || ($response->json()['status'] ?? '') !== 'ok') {
                    return back()->with('error', 'Failed to delete from ChromaDB: '.($response->body() ?? 'Unknown error'));
                }

                $this->deleteNodesByChunkIds($chunkIds);

                return back()->with('success', 'Selected chunks deleted successfully (legacy mode).');
            }

            // Delete chunks by parent IDs
            $parentIds = array_unique($foundParentIds);
            $deletePayload = [];
            if (! empty($parentIds)) {
                $deletePayload['parent_ids'] = array_values($parentIds);
            }
            if (! empty($notFoundIds)) {
                $deletePayload['ids'] = array_values($notFoundIds);
            }

            if (! empty($deletePayload)) {
                $response = Http::timeout(30)->asJson()->post("$this->apiBase/delete_batch", $deletePayload);
                if (! $response->successful() || ($response->json()['status'] ?? '') !== 'ok') {
                    return back()->with('error', 'Failed to delete from ChromaDB: '.($response->body() ?? 'Unknown error'));
                }
            }

            // Delete nodes in DB by parent IDs
            $deletedCount = 0;
            foreach ($parentIds as $parentId) {
                $embedding = NodeEmbedding::where('chroma_id', $parentId)->first();
                if ($embedding && $embedding->node) {
                    $embedding->node->delete();
                    $deletedCount++;
                }
            }

            $message = $deletedCount > 0
                ? "Successfully deleted {$deletedCount} article(s) with all their chunks from ChromaDB and database."
                : 'Chunks deleted from ChromaDB, but no matching articles found in database.';

            if (! empty($notFoundIds)) {
                $message .= ' Some chunks were not found in ChromaDB and were deleted directly.';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error during deletion: '.$e->getMessage());
        }
    }

    /**
     * Delete nodes by chunk IDs for backward compatibility.
     */
    private function deleteNodesByChunkIds(array $chunkIds): void
    {
        $parentIdMap = $this->embeddingService->getParentIds($chunkIds);
        $parentIds = array_unique(array_values($parentIdMap));

        foreach ($parentIds as $parentId) {
            $embedding = NodeEmbedding::where('chroma_id', $parentId)->first();
            if ($embedding && $embedding->node) {
                $embedding->node->delete();
            }
        }
    }
}
