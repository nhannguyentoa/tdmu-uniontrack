<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EvaluationReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle
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
}
