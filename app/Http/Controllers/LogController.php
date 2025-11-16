<?php

namespace App\Http\Controllers;

use App\Models\Log;

/**
 * Class LogController
 *
 * Controller for managing application logs.
 */
class LogController extends Controller
{
    /**
     * Display a paginated list of logs.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get the latest logs and paginate 25 per page
        $logs = Log::latest()->paginate(25);

        return view('parser.logs.index', compact('logs'));
    }

    /**
     * Clear all logs from the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clear()
    {
        // Truncate the logs table
        Log::truncate();

        return redirect()
            ->route('parser.logs')
            ->with('status', 'The log has been cleared successfully.');
    }
}
