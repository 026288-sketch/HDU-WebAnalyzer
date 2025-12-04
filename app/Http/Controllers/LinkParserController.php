<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Services\Logs\LoggerService;
use App\Services\Parser\ArticleFinder;
use Illuminate\Http\Request;

/**
 * Class LinkParserController
 *
 * Controller for managing the link parser.
 */
class LinkParserController extends Controller
{
    protected ArticleFinder $finder;

    protected LoggerService $logger;

    public function __construct(ArticleFinder $finder, LoggerService $logger)
    {
        $this->finder = $finder;
        $this->logger = $logger;
    }

    /**
     * Show the link parser page.
     */
    public function index()
    {
        return view('parser.links');
    }

    /**
     * Run the parser for the active source.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function run(Request $request)
    {
        // Get the currently active source or fallback to the first source
        $source = Source::where('isActive', 1)->first() ?? Source::first();

        if (! $source) {
            $this->logger->log('SourceService', 'ERROR', 'No sources in the database');

            return view('parser.links', ['error' => 'No sources in the database']);
        }

        // Switch the active source
        $nextSource = Source::where('id', '>', $source->id)->first() ?? Source::first();
        Source::query()->update(['isActive' => 0]);

        if ($nextSource) {
            $nextSource->isActive = 1;
            $nextSource->save();
        }

        // Load the regex file
        $regexPath = storage_path('app/regex.txt');

        if (! file_exists($regexPath)) {
            $this->logger->log('SourceService', 'ERROR', 'Regex file not found', ['path' => $regexPath]);

            return view('parser.links', ['error' => 'Regex file not found']);
        }

        $regexFile = trim(file_get_contents($regexPath));
        $regex = $source->regex ?? $regexFile;

        // Parse articles using the regex
        $result = $this->finder->findArticlesByRegex($source->url, $regex);

        // Map articles to array format
        $links = collect($result['articles'])->map(fn ($url) => ['url' => $url])->toArray();
        $html = $result['html'];

        return view('parser.links', compact('links', 'source', 'html', 'regex'));
    }
}
