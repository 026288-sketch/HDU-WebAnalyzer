<?php

namespace App\Http\Controllers;

use App\Services\Parser\ArticleContentParser;
use App\Services\Parser\ArticleFinder;
use Illuminate\Http\Request;

/**
 * Class TestParserController
 *
 * Controller to test article link finding and content parsing.
 * Provides a simple interface to test parsing by URL and regex patterns.
 */
class TestParserController extends Controller
{
    /**
     * Show the test parser page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('parser.test-parser');
    }

    /**
     * Test article link extraction using a URL and regex.
     *
     * @return \Illuminate\View\View
     */
    public function testLinks(Request $request)
    {
        $url = $request->input('source_url');
        $regex = $request->input('regex');

        // Resolve the ArticleFinder service from the container
        $finder = app()->make(ArticleFinder::class);

        // Find articles based on provided URL and regex
        $results = $finder->findArticlesByRegex($url, $regex);

        // Return view with results for display
        return view('parser.test-parser', [
            'linkResults' => $results['articles'] ?? [],
            'linkHtml' => $results['html'] ?? '',
            'sourceUrl' => $url,
            'regex' => $regex,
        ]);
    }

    /**
     * Test parsing the full content of an article by URL.
     *
     * @return \Illuminate\View\View
     */
    public function testContent(Request $request)
    {
        $url = $request->input('article_url');
        $useBrowser = $request->has('use_browser');

        // Resolve the ArticleContentParser service from the container
        $parser = app()->make(ArticleContentParser::class);

        // Parse the HTML content of the article
        $result = $parser->parseHtml($url, $useBrowser);

        // Return view with parsing results
        return view('parser.test-parser', [
            'contentResults' => $result,
            'articleUrl' => $url,
            'useBrowser' => $useBrowser,
        ]);
    }
}
