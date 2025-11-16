<?php

namespace App\Http\Controllers;

use App\Exports\NodesExport;
use App\Exports\StatsExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class ExportController
 *
 * Handles exporting of nodes and statistical data to Excel.
 */
class ExportController extends Controller
{
    /**
     * Export nodes to an Excel file with optional selected fields.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportNodes(Request $request)
    {
        $fields = $request->input('fields', []);

        $fileName = 'nodes_'.now()->format('Y-m-d_H-i').'.xlsx';

        return Excel::download(
            new NodesExport($fields),
            $fileName,
            ExcelType::XLSX
        );
    }

    /**
     * Export statistics to an Excel file for a given date range and grouping.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportStats(Request $request)
    {
        $startDate = $request->input('date_from', Carbon::today()->subDays(30)->toDateString());
        $endDate = $request->input('date_to', Carbon::today()->toDateString());
        $group = $request->input('group', 'daily');

        $fileName = 'stats_'.$group.'_'.now()->format('Y-m-d_H-i').'.xlsx';

        return Excel::download(
            new StatsExport($startDate, $endDate, $group),
            $fileName,
            ExcelType::XLSX
        );
    }
}
