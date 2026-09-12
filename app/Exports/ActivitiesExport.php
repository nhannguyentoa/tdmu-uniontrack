<?php

namespace App\Exports;

use App\Models\Activity;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ActivitiesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(protected Collection $activities)
    {
    }

    public function collection(): Collection
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return ['Mã hoạt động', 'Tên hoạt động', 'Tổ công đoàn', 'Loại hoạt động', 'Thời gian bắt đầu', 'Thời gian kết thúc', 'Địa điểm', 'Trạng thái', 'Tiến độ (%)', 'SL dự kiến', 'SL thực tế', 'Điểm thi đua tối đa', 'Điểm thi đua đã đạt'];
    }

    public function map($activity): array
    {
        return [
            $activity->code,
            $activity->name,
            $activity->unionGroup?->name,
            $activity->activityType?->name,
            $activity->start_time?->format('d/m/Y H:i'),
            $activity->end_time?->format('d/m/Y H:i'),
            $activity->location,
            Activity::STATUSES[$activity->status] ?? $activity->status,
            $activity->progress,
            $activity->expected_quantity,
            $activity->actual_quantity,
            $activity->counts_for_evaluation ? $activity->evaluation_max_score : '',
            $activity->counts_for_evaluation ? $activity->earnedEvaluationScore() : '',
        ];
    }

    public function title(): string
    {
        return 'Danh sách hoạt động';
    }

    public function styles(Worksheet $sheet): ?array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:M{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:H'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I2:M'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 30, 'C' => 18, 'D' => 18,
            'E' => 18, 'F' => 18, 'G' => 22, 'H' => 16,
            'I' => 12, 'J' => 12, 'K' => 12, 'L' => 16, 'M' => 16,
        ];
    }
}
