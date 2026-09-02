<?php

namespace App\Http\Controllers;

use App\Exports\EmployeePerformanceExport;
use App\Models\Branch;
use App\Models\Department;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PRD 12 — download endpoints for App\Filament\Pages\Reports. Not tied to one
 * Eloquent model instance (unlike TravelAssignmentPdfController), so authorization
 * is a plain role check here rather than a Policy class — same role set as
 * Reports::canAccess() and TeamLeaveCalendar::canAccess().
 */
class ReportExportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function employeePerformanceExcel(Request $request): BinaryFileResponse
    {
        $this->authorize($request);

        return Excel::download(
            new EmployeePerformanceExport($this->reports->employeePerformance($this->filters($request))),
            'laporan-performa-karyawan-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    public function employeePerformancePdf(Request $request): Response
    {
        $this->authorize($request);

        $filters = $this->filters($request);

        return Pdf::loadView('pdf.reports.employee-performance', [
            'rows' => $this->reports->employeePerformance($filters),
            'filters' => $filters,
            ...$this->filterLabels($filters),
        ])->stream('laporan-performa-karyawan-' . now()->format('Y-m-d') . '.pdf');
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['administrator', 'hr', 'direksi']), 403);
    }

    /**
     * @return array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}
     */
    private function filters(Request $request): array
    {
        return [
            'start_date' => $request->query('start_date') ?: now()->startOfMonth()->toDateString(),
            'end_date' => $request->query('end_date') ?: now()->toDateString(),
            'department_id' => $request->query('department_id') ?: null,
            'branch_id' => $request->query('branch_id') ?: null,
        ];
    }

    /**
     * @param  array{department_id: ?int, branch_id: ?int}  $filters
     * @return array{departmentName: ?string, branchName: ?string}
     */
    private function filterLabels(array $filters): array
    {
        return [
            'departmentName' => $filters['department_id'] ? Department::find($filters['department_id'])?->name : null,
            'branchName' => $filters['branch_id'] ? Branch::find($filters['branch_id'])?->name : null,
        ];
    }
}
