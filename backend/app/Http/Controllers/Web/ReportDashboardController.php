<?php

namespace App\Http\Controllers\Web;

use App\Exports\DashboardReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportDateRangeRequest;
use App\Services\ReportDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportDashboardController extends Controller
{
    public function index(ReportDateRangeRequest $request, ReportDashboardService $service)
    {
        [$from, $to, $preset] = $request->resolveRange();

        $data = $service->build($from, $to);

        return view('reports.dashboard', $data + ['preset' => $preset]);
    }

    public function exportPdf(ReportDateRangeRequest $request, ReportDashboardService $service)
    {
        [$from, $to] = $request->resolveRange();

        $data = $service->build($from, $to);

        $filename = 'sales-report-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.pdf';

        return Pdf::loadView('reports.dashboard-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function exportExcel(ReportDateRangeRequest $request, ReportDashboardService $service)
    {
        [$from, $to] = $request->resolveRange();

        $data = $service->build($from, $to);

        $filename = 'sales-report-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.xlsx';

        return Excel::download(new DashboardReportExport($data), $filename);
    }
}
