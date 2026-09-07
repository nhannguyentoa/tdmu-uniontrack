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
class ActivitiesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths{
    public function __construct(protected Collection $activities)
    {
    }

    public function collection(): Collection
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return ['Mã hoạt động', 'Tên hoạt động', 'Tổ công đoàn', 'Loại hoạt động', 'Thời gian bắt đầu', 'Thời gian kết thúc', 'Địa điểm', 'Trạng thái', 'Tiến độ (%)', 'SL dự kiến', 'SL thực tế'];
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
        ];
    }

    public function title(): string
    {
        return 'Danh sách hoạt động';
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K'.$sheet->getHighestRow())
              ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        return [];
    }

    public function columnWidths(): array
    {
        return ['B' => 30];
    }
}
