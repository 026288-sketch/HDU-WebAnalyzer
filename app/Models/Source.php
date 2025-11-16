<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $fillable = ['url', 'isActive', 'rss_url', 'full_rss_content', 'need_browser'];
}
