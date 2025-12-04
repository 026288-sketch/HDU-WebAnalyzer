<?php

namespace App\Helpers;

use App\Models\NodeEmbedding;

/**
 * Class ChromaIdHelper
 *
 * Utility class for handling Chroma embedding IDs.
 * Centralizes logic for extracting base IDs and finding original nodes.
 *
 * ChromaID format from Python: "parent_id_0", "parent_id_1", "parent_id_2"
 * Base ID (parent_id) is stored in NodeEmbedding.chroma_id
 *
 * Usage:
 *   $baseId = ChromaIdHelper::extractBaseId('parent_id_0'); // Returns: 'parent_id'
 *   $nodeId = ChromaIdHelper::findOriginalNodeId('parent_id_0'); // Returns: node_id or null
 */
class ChromaIdHelper
{
    /**
     * Delimiter used in ChromaID format
     */
    private const CHROMA_ID_DELIMITER = '_';

    /**
     * Extract base ID from a full ChromaID
     *
     * Handles ChromaID format: "parent_id_chunk_number"
     * Example: "abc123_0" → "abc123"
     * Also handles simple base_id without chunks
     *
     * @param  string|null  $chromaId  Full ChromaID string
     * @return string|null Base ID or null if invalid
     */
    public static function extractBaseId(?string $chromaId): ?string
    {
        if (empty($chromaId)) {
            return null;
        }

        $chromaId = trim($chromaId);
        $parts = explode(self::CHROMA_ID_DELIMITER, $chromaId);

        // Invalid format - should have at least one part
        if (empty($parts) || empty($parts[0])) {
            return null;
        }

        // If only one part, it's already a base ID
        if (count($parts) === 1) {
            return $parts[0];
        }

        // If last part is numeric, it's a chunk number - return everything except last part
        $lastPart = end($parts);
        if (is_numeric($lastPart)) {
            array_pop($parts);

            return implode(self::CHROMA_ID_DELIMITER, $parts);
        }

        // Otherwise return first part
        return $parts[0];
    }

    /**
     * Find the original Node ID by ChromaID
     *
     * Extracts base ID from ChromaID and searches for associated Node in embeddings table.
     * Returns the node_id of the original article that was embedded.
     *
     * @param  string|null  $chromaId  Full ChromaID string
     * @return int|null Node ID or null if not found
     */
    public static function findOriginalNodeId(?string $chromaId): ?int
    {
        if (empty($chromaId)) {
            return null;
        }

        $baseId = self::extractBaseId($chromaId);

        if (empty($baseId)) {
            return null;
        }

        // Search for the embedding with this base ID
        $embedding = NodeEmbedding::where('chroma_id', $baseId)->first();

        return $embedding?->node_id;
    }

    /**
     * Validate ChromaID format
     *
     * Checks if the ChromaID is not empty and has valid structure
     *
     * @param  string|null  $chromaId  ChromaID to validate
     * @return bool True if valid format, false otherwise
     */
    public static function isValid(?string $chromaId): bool
    {
        if (empty($chromaId)) {
            return false;
        }

        $baseId = self::extractBaseId($chromaId);

        return ! empty($baseId) && is_string($baseId);
    }

    /**
     * Get chunk number from ChromaID if available
     *
     * Extracts the chunk number from ChromaID format.
     * Example: "abc123_2" → 2
     * Example: "abc123" → null (no chunk number)
     *
     * @param  string|null  $chromaId  Full ChromaID string
     * @return int|null Chunk number or null if not found
     */
    public static function getChunkNumber(?string $chromaId): ?int
    {
        if (empty($chromaId)) {
            return null;
        }

        $chromaId = trim($chromaId);
        $parts = explode(self::CHROMA_ID_DELIMITER, $chromaId);

        if (count($parts) < 2) {
            return null;
        }

        // Last part should be numeric
        $lastPart = end($parts);

        if (! is_numeric($lastPart)) {
            return null;
        }

        $chunkNumber = (int) $lastPart;

        return $chunkNumber >= 0 ? $chunkNumber : null;
    }

    /**
     * Compare two ChromaIDs
     *
     * Checks if two ChromaIDs refer to the same base embedding.
     * Useful for duplicate checking across chunks.
     *
     * @param  string|null  $chromaId1  First ChromaID
     * @param  string|null  $chromaId2  Second ChromaID
     * @return bool True if both have the same base ID
     */
    public static function sameBase(?string $chromaId1, ?string $chromaId2): bool
    {
        $baseId1 = self::extractBaseId($chromaId1);
        $baseId2 = self::extractBaseId($chromaId2);

        if (empty($baseId1) || empty($baseId2)) {
            return false;
        }

        return $baseId1 === $baseId2;
    }

    /**
     * Get all unique base IDs from a collection of ChromaIDs
     *
     * @param  array  $chromaIds  Array of ChromaIDs
     * @return array Array of unique base IDs
     */
    public static function extractUniqueBaseIds(array $chromaIds): array
    {
        $baseIds = array_map(
            fn ($id) => self::extractBaseId($id),
            $chromaIds
        );

        return array_values(array_unique(array_filter($baseIds)));
    }

    /**
     * Filter valid ChromaIDs from a collection
     *
     * @param  array  $chromaIds  Array of ChromaIDs to validate
     * @return array Array of valid ChromaIDs
     */
    public static function filterValid(array $chromaIds): array
    {
        return array_filter($chromaIds, fn ($id) => self::isValid($id));
    }

    /**
     * Get invalid ChromaIDs from a collection
     *
     * @param  array  $chromaIds  Array of ChromaIDs to check
     * @return array Array of invalid ChromaIDs
     */
    public static function getInvalid(array $chromaIds): array
    {
        $validIds = self::filterValid($chromaIds);

        return array_diff($chromaIds, $validIds);
    }
}
