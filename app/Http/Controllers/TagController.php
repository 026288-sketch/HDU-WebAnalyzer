<?php

namespace App\Http\Controllers;

use App\Models\Tag;

/**
 * Class TagController
 *
 * Controller to display articles associated with a specific tag.
 */
class TagController extends Controller
{
    /**
     * Display a list of articles filtered by a specific tag.
     *
     * @param  string  $slug  The slug identifier of the tag
     * @return \Illuminate\View\View
     */
    public function show(string $slug)
    {
        // Find tag by slug, throw 404 if not found
        $tag = Tag::where('slug', $slug)->firstOrFail();

        // Get articles associated with this tag, ordered by newest first
        $articles = $tag->nodes()
            ->orderByDesc('timestamp')
            ->paginate(10);

        // Return view with tag and its articles
        return view('parser.articles_by_tag', compact('tag', 'articles'));
    }
}
