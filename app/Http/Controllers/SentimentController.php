<?php

namespace App\Http\Controllers;

use App\Models\Node;

/**
 * Class SentimentController
 *
 * Controller for displaying articles filtered by sentiment or emotion.
 */
class SentimentController extends Controller
{
    /**
     * Display a list of articles filtered by sentiment or emotion.
     *
     * @param  string  $type  Either 'sentiment' or 'emotion'
     * @param  string  $value  The value of sentiment/emotion to filter by
     * @return \Illuminate\View\View
     */
    public function show(string $type, string $value)
    {
        // Validate type: only 'sentiment' or 'emotion' is allowed
        abort_unless(in_array($type, ['sentiment', 'emotion']), 404);

        // Fetch articles filtered by the chosen sentiment or emotion
        $articles = Node::whereHas('sentiment', function ($query) use ($type, $value) {
            $query->where($type, $value);
        })->latest()->paginate(10);

        // Set page title based on filter type
        $title = $type === 'sentiment'
            ? "🧭 Articles with sentiment: {$value}"
            : "💭 Articles with emotion: {$value}";

        return view('sentiments.list', compact('articles', 'title', 'type', 'value'));
    }
}
