<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodeLink extends Model
{
    protected $fillable = [
        'url',
        'parsed',
        'source',
        'type',
        'use_browser',
        'attempts',
        'last_error',
        'is_duplicate',
        'duplicate_of',
    ];
}
