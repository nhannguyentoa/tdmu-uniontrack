<?php

namespace App\Exports\Activities;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Khung chung cho các sheet của file "Danh sách hoạt động": nội dung được ghi trong AfterSheet
 * (qua writeGrid) để kiểm soát kiểu dữ liệu từng ô, nên FromArray chỉ trả về mảng rỗng.
 */
abstract class ActivitySheet implements FromArray, WithEvents, WithStrictNullComparison, WithTitle
{
    use StylesWorkbook;

    /** @param  array{exported_at: \DateTimeInterface, exported_by: string, filters: string}  $meta */
    public function __construct(protected ActivityAggregator $data, protected array $meta) {}

    abstract protected function build(Worksheet $ws): void;

    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();
                $this->baseStyle($ws);
                $this->build($ws);
            },
        ];
    }
}
