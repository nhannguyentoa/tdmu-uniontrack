<?php

namespace App\Exports\Members;

use App\Exports\Activities\StylesWorkbook;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet cuối "Tổng hợp": mỗi tổ một dòng, đếm đoàn viên theo trạng thái.
 */
class MemberSummarySheet implements FromArray, WithEvents, WithStrictNullComparison, WithTitle
{
    use StylesWorkbook;

    public const TITLE = 'Tổng hợp';

    /** @param  list<array{name: string, sheet: string, counts: array<string, int>, total: int}>  $rows */
    public function __construct(protected array $rows, protected array $meta) {}

    public function title(): string
    {
        return self::TITLE;
    }

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

    protected function build(Worksheet $ws): void
    {
        $this->titleRows($ws, 'TỔNG HỢP ĐOÀN VIÊN THEO TỔ CÔNG ĐOÀN', $this->metaLine($this->meta), 'F');

        $grid = [['STT', 'Tổ công đoàn', 'Tổng số đoàn viên', 'Đang hoạt động', 'Ngưng hoạt động', 'Đã chuyển']];
        foreach ($this->rows as $i => $row) {
            $grid[] = [$i + 1, $row['name'], $row['total'], $row['counts']['active'], $row['counts']['inactive'], $row['counts']['transferred']];
        }
        $this->writeGrid($ws, $grid, 4);

        $first = 5;
        $last = 4 + count($this->rows);
        $total = $last + 1;
        $ws->setCellValue("A{$total}", 'TỔNG CỘNG');
        $ws->mergeCells("A{$total}:B{$total}");
        foreach (['C', 'D', 'E', 'F'] as $col) {
            $ws->setCellValue("{$col}{$total}", "=SUM({$col}{$first}:{$col}{$last})");
        }

        // Tên tổ là liên kết tới sheet của tổ đó.
        foreach ($this->rows as $i => $row) {
            $cell = $ws->getCell('B'.($first + $i));
            $cell->getHyperlink()->setUrl("sheet://'".str_replace("'", "''", $row['sheet'])."'!A1");
            $cell->getStyle()->getFont()->setUnderline(true)->getColor()->setRGB('1F4E78');
        }

        $this->widths($ws, ['A' => 6, 'B' => 36, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 12]);
        $this->headerStyle($ws, 'A4:F4');
        $this->borders($ws, "A4:F{$total}");
        $this->align($ws, "A{$first}:F{$total}", Alignment::HORIZONTAL_CENTER);
        $this->align($ws, "B{$first}:B{$last}", Alignment::HORIZONTAL_LEFT);
        $this->fill($ws, "A{$total}:F{$total}", self::TOTAL_FILL, true);

        $ws->freezePane('A5');
        $this->autoRowHeights($ws, 2, $total);
        $this->pageSetup($ws, repeatRows: [4, 4]);
    }
}
