<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(protected array $report)
    {
    }

    public function array(): array
    {
        return $this->report['activities']->map(function (Activity $activity) {
            return [
                $activity->code,
                $activity->name,
                $activity->unionGroup?->name,
                $activity->activityType?->name,
                $activity->start_time?->format('d/m/Y H:i'),
                $activity->end_time?->format('d/m/Y H:i'),
                Activity::STATUSES[$activity->status] ?? $activity->status,
                $activity->progress.'%',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            ['TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT'],
            ['BÁO CÁO HOẠT ĐỘNG CÔNG ĐOÀN — '.mb_strtoupper($this->report['label'])],
            ['Tổng số hoạt động: '.$this->report['total_activities'].' | Hoàn thành: '.$this->report['completed_activities'].' | Tỷ lệ hoàn thành: '.$this->report['completion_rate'].'% | Lượt tham gia: '.$this->report['total_participants']],
            [],
            ['Mã HĐ', 'Tên hoạt động', 'Tổ công đoàn', 'Loại hoạt động', 'Bắt đầu', 'Kết thúc', 'Trạng thái', 'Tiến độ'],
        ];
    }

    public function title(): string
    {
        return 'Báo cáo';
    }

    public function styles(Worksheet $sheet): ?array
    {
        $lastRow = $sheet->getHighestRow();

        // 3 dòng tiêu đề văn bản đầu (dòng 4 để trống)
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // dòng 5 là hàng tiêu đề bảng thật (do có 3 dòng text + 1 dòng trống ở trên)
        $sheet->getStyle('A5:H5')->getFont()->setBold(true);
        $sheet->getStyle('A5:H5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A5:H{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('E6:H'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 12, 'B' => 30, 'C' => 18, 'D' => 18, 'E' => 18, 'F' => 18, 'G' => 16, 'H' => 12];
    }
}
