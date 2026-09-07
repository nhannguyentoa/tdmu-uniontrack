<?php

namespace App\Exports;

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

class EvaluationReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(protected Collection $summary, protected string $academicYear)
    {
    }

    public function collection(): Collection
    {
        return $this->summary->values();
    }

    public function headings(): array
    {
        return ['STT', 'Tên tổ Công đoàn', 'Điểm chuẩn', 'Tổng tự chấm', 'Tổng thẩm định', 'Chênh lệch (TĐ-TC)', 'Xếp loại'];
    }

    public function map($row): array
    {
        static $stt = 0;
        $stt++;

        return [
            $stt,
            $row['union_group']->name,
            $row['standard_total'],
            $row['self_total'],
            $row['verified_total'],
            $row['diff'],
            $row['classification'],
        ];
    }

    public function title(): string
    {
        return 'Báo cáo Hội đồng '.$this->academicYear;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:G{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:F'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:G'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 26, 'C' => 14, 'D' => 16, 'E' => 16, 'F' => 18, 'G' => 24];
    }
}
