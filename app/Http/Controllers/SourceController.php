<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Services\Source\SourceService;
use Illuminate\Http\Request;

/**
 * Class SourceController
 *
 * Controller to manage sources for news parsing.
 */
class SourceController extends Controller
{
    protected SourceService $service;

    /**
     * SourceController constructor.
     */
    public function __construct(SourceService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a list of all sources.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get all sources from database
        $sources = Source::all();

        // Return view with sources
        return view('sources.index', compact('sources'));
    }

    /**
     * Store a new source.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate input: URL is required, must be a valid URL, and unique
        $request->validate([
            'url' => 'required|url|unique:sources,url',
        ]);

        // Add source via the service
        $this->service->addSource($request->input('url'));

        // Redirect back with success message
        return redirect()->back()->with('success', '✅ Source added successfully.');
    }

    /**
     * Delete a source.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Source $source)
    {
        // Delete source via the service
        $this->service->deleteSource($source);

        // Redirect to sources list with success message
        return redirect()->route('sources.index')->with('success', '✅ Source deleted successfully.');
    }
}
