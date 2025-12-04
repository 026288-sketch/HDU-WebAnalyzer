<?php

namespace App\Models;

use App\Services\EmbeddingService;
use App\Services\Logs\LoggerService;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $fillable = [
        'title', 'content', 'summary', 'url', 'timestamp', 'hash', 'image',
    ];

    public function embedding()
    {
        return $this->hasOne(NodeEmbedding::class);
    }

    protected static function booted()
    {
        static::deleting(function ($node) {
            $node->nodeLinks()->delete();

            if ($node->sentiment) {
                $node->sentiment->delete();
            }

            $embedding = $node->embedding;

            if ($embedding && $embedding->chroma_id) {
                $chromaId = $embedding->chroma_id;
                $embedding->delete();

                try {
                    $embeddingService = app(EmbeddingService::class);
                    $deleted = $embeddingService->deleteByParentId($chromaId);

                    $logger = app(LoggerService::class);

                    if ($deleted) {
                        $logger->log('Node', 'INFO', 'Deleted ChromaDB embedding', [
                            'node_id' => $node->id,
                            'chroma_id' => $chromaId,
                        ]);
                    } else {
                        $logger->log('Node', 'WARNING', 'Failed to delete ChromaDB embedding', [
                            'node_id' => $node->id,
                            'chroma_id' => $chromaId,
                        ]);
                    }
                } catch (\Exception $e) {
                    $logger = app(LoggerService::class);
                    $logger->log('Node', 'ERROR', 'Error deleting embedding from ChromaDB', [
                        'node_id' => $node->id,
                        'chroma_id' => $chromaId,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'node_tag', 'node_id', 'tag_id');
    }

    public function sentiment()
    {
        return $this->hasOne(NodeSentiment::class);
    }

    public function nodeLinks()
    {
        return $this->hasMany(NodeLink::class, 'url', 'url');
    }
}
