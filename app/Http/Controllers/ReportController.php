<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\UnionGroup;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports)
    {
    }

    public function month(Request $request): View
    {
        $year = $request->integer('year') ?: now()->year;
        $month = $request->integer('month') ?: now()->month;
        $unionGroupId = $this->resolveUnionGroupId($request);

        $report = $this->reports->monthlyReport($year, $month, $unionGroupId);

        return view('reports.month', [
            'report' => $report,
            'unionGroups' => $this->unionGroupOptions($request),
            'selectedUnionGroupId' => $unionGroupId,
        ]);
    }

    public function semester(Request $request): View
    {
        $year = $request->integer('year') ?: now()->year;
        $semester = $request->integer('semester') ?: (now()->month <= 6 ? 1 : 2);
        $unionGroupId = $this->resolveUnionGroupId($request);

        $report = $this->reports->semesterReport($year, $semester, $unionGroupId);

        return view('reports.semester', [
            'report' => $report,
            'unionGroups' => $this->unionGroupOptions($request),
            'selectedUnionGroupId' => $unionGroupId,
        ]);
    }

    public function year(Request $request): View
    {
        $year = $request->integer('year') ?: now()->year;
        $unionGroupId = $this->resolveUnionGroupId($request);

        $report = $this->reports->yearlyReport($year, $unionGroupId);

        return view('reports.year', [
            'report' => $report,
            'unionGroups' => $this->unionGroupOptions($request),
            'selectedUnionGroupId' => $unionGroupId,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $report = $this->resolveReport($request);

        return Excel::download(new ReportExport($report), 'bao-cao-cong-doan.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $report = $this->resolveReport($request);

        $pdf = Pdf::loadView('reports.pdf', compact('report'))->setPaper('a4', 'portrait');

        return $pdf->download('bao-cao-cong-doan.pdf');
    }

    protected function resolveReport(Request $request): array
    {
        $type = $request->string('type', 'month');
        $year = $request->integer('year') ?: now()->year;
        $unionGroupId = $this->resolveUnionGroupId($request);

        return match ((string) $type) {
            'semester' => $this->reports->semesterReport($year, $request->integer('semester') ?: 1, $unionGroupId),
            'year' => $this->reports->yearlyReport($year, $unionGroupId),
            default => $this->reports->monthlyReport($year, $request->integer('month') ?: now()->month, $unionGroupId),
        };
    }

    protected function resolveUnionGroupId(Request $request): ?int
    {
        $user = $request->user();
        $unionGroupId = $request->integer('union_group_id') ?: null;

        if ($user->isOfficer()) {
            $managedIds = $user->managedUnionGroups()->pluck('union_groups.id')->all();

            return in_array($unionGroupId, $managedIds, true) ? $unionGroupId : ($managedIds[0] ?? null);
        }

        return $unionGroupId;
    }

    protected function unionGroupOptions(Request $request)
    {
        $user = $request->user();

        return $user->isOfficer()
            ? $user->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();
    }
}
