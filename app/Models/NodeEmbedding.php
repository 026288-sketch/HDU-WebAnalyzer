<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeEmbedding extends Model
{
    protected $fillable = [
        'node_id',
        'chroma_id',
        'similarity',
    ];

    protected $casts = [
        'similarity' => 'float',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
