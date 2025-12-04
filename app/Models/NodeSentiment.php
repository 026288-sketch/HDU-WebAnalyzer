<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeSentiment extends Model
{
    protected $fillable = ['node_id', 'sentiment', 'emotion'];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
