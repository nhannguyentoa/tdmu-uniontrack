<?php

namespace App\Exports\Activities;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Theo tổ công đoàn": danh sách hoạt động theo từng tổ, hoạt động trùng tên trong cùng tổ được gom thành một dòng.
 */
class ByGroupSheet extends ActivitySheet
{
    public const TITLE = 'Theo tổ công đoàn';

    protected const FIRST_ROW = 6;

    public function title(): string
    {
        return self::TITLE;
    }

    protected function build(Worksheet $ws): void
    {
        $this->titleRows($ws, 'DANH SÁCH HOẠT ĐỘNG CÔNG ĐOÀN THEO TỔ', 'Công đoàn Trường Đại học Thủ Dầu Một · '.$this->metaLine($this->meta), 'O');

        $this->writeGrid($ws, [
            ['Tổ công đoàn', 'STT', 'Hoạt động', null, null, null, null, null, null, 'Tiến độ & tham gia', null, null, null, 'Thi đua'],
            [null, null, 'Tên hoạt động', 'Số lần tổ chức', 'Mã hoạt động', 'Loại hoạt động', 'Từ ngày', 'Đến ngày', 'Số lần đã hoàn thành',
                'Tiến độ TB', 'SL dự kiến', 'SL thực tế', 'Tỷ lệ tham gia', 'Điểm tối đa', 'Điểm đạt'],
        ], 4);
        foreach (['A4:A5', 'B4:B5', 'C4:I4', 'J4:M4', 'N4:O4'] as $range) {
            $ws->mergeCells($range);
        }
        $this->headerStyle($ws, 'A4:O5');

        $row = self::FIRST_ROW;
        $merged = []; // dòng có Số lần > 1 để tô đỏ
        $groupStarts = [];

        foreach ($this->data->byGroup() as $block) {
            $start = $row;

            foreach ($block['rows'] as $i => $item) {
                $this->writeGrid($ws, [[
                    $i === 0 ? $block['group']->name : null,
                    $i + 1,
                    $item['name'],
                    $item['count'],
                    implode("\n", $item['codes']),
                    implode("\n", $item['types']) ?: null,
                    $item['start'] ? Date::dateTimeToExcel($item['start']->copy()->startOfDay()) : null,
                    $item['end'] ? Date::dateTimeToExcel($item['end']->copy()->startOfDay()) : null,
                    $item['completed'],
                    $item['progress'],
                    $item['expected'],
                    $item['actual'],
                    new Formula("=IFERROR(L{$row}/K{$row},0)"),
                    $item['max_score'],
                    $item['earned'],
                ]], $row);

                if ($item['count'] > 1) {
                    $merged[] = $row;
                }
                $row++;
            }

            $end = $row - 1;
            $sum = fn (string $col) => new Formula("=SUM({$col}{$start}:{$col}{$end})");
            $this->writeGrid($ws, [[
                null, null, 'Cộng tổ', $sum('D'), null, null, null, null, $sum('I'),
                $block['total']['progress'], $sum('K'), $sum('L'), new Formula("=IFERROR(L{$row}/K{$row},0)"), $sum('N'), $sum('O'),
            ]], $row);
            $this->fill($ws, "B{$row}:O{$row}", self::SUBTOTAL_FILL, true);
            $ws->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $ws->mergeCells("A{$start}:A{$row}");
            $groupStarts[] = $start;
            $row++;
        }

        if ($row === self::FIRST_ROW) {
            $ws->setCellValue('C6', 'Không có hoạt động nào phù hợp bộ lọc.');
            $row++;
        }

        // Dòng tổng toàn trường cộng các dòng "Cộng tổ" để tự cập nhật khi sửa số trong bảng.
        $last = $row - 1;
        $total = $row;
        $sumIf = fn (string $col) => new Formula('=SUMIF($C$'.self::FIRST_ROW.":\$C\${$last},\"Cộng tổ\",{$col}".self::FIRST_ROW.":{$col}{$last})");
        $all = $this->data->details()->pluck('progress')->filter(fn ($p) => $p !== null);
        $this->writeGrid($ws, [[
            'TỔNG CỘNG TOÀN TRƯỜNG', null, null, $sumIf('D'), null, null, null, null, $sumIf('I'),
            $all->isEmpty() ? null : round($all->avg() / 100, 4), $sumIf('K'), $sumIf('L'),
            new Formula("=IFERROR(L{$total}/K{$total},0)"), $sumIf('N'), $sumIf('O'),
        ]], $total);
        $ws->mergeCells("A{$total}:C{$total}");
        $this->fill($ws, "A{$total}:O{$total}", self::TOTAL_FILL, true);

        $ws->setCellValue('A'.($total + 2), 'Số màu đỏ ở cột "Số lần tổ chức" = hoạt động trùng tên trong cùng tổ đã được gom thành một dòng; mã và loại của từng lần ghi ở cột Mã hoạt động, Loại hoạt động.');
        $ws->getStyle('A'.($total + 2))->getFont()->setItalic(true)->getColor()->setRGB(self::GREY_TEXT);

        $this->widths($ws, [
            'A' => 22, 'B' => 6, 'C' => 52, 'D' => 9, 'E' => 15, 'F' => 31, 'G' => 12, 'H' => 12,
            'I' => 11, 'J' => 9, 'K' => 10, 'L' => 10, 'M' => 10, 'N' => 9, 'O' => 10,
        ]);

        $span = fn (string $col) => $col.self::FIRST_ROW.":{$col}{$total}";
        $this->borders($ws, "A4:O{$total}");
        $this->align($ws, $span('A'), Alignment::HORIZONTAL_CENTER);
        $this->align($ws, 'B'.self::FIRST_ROW.":O{$total}", Alignment::HORIZONTAL_CENTER);
        foreach (['C', 'E', 'F'] as $col) {
            $this->align($ws, $col.self::FIRST_ROW.':'.$col.($total - 1), Alignment::HORIZONTAL_LEFT);
        }
        foreach ($groupStarts as $r) {
            $this->fill($ws, "A{$r}", self::GROUP_FILL, true);
        }
        $ws->getStyle("A{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach ($merged as $r) {
            $ws->getStyle("D{$r}")->getFont()->setBold(true)->getColor()->setRGB(self::RED);
        }

        $ws->getStyle($span('G'))->getNumberFormat()->setFormatCode(self::FMT_DATE);
        $ws->getStyle($span('H'))->getNumberFormat()->setFormatCode(self::FMT_DATE);
        $ws->getStyle($span('J'))->getNumberFormat()->setFormatCode(self::FMT_PCT);
        $ws->getStyle($span('M'))->getNumberFormat()->setFormatCode(self::FMT_PCT);
        $ws->getStyle($span('K'))->getNumberFormat()->setFormatCode(self::FMT_QTY);
        $ws->getStyle($span('L'))->getNumberFormat()->setFormatCode(self::FMT_QTY);
        $ws->getStyle($span('N'))->getNumberFormat()->setFormatCode(self::FMT_SCORE);
        $ws->getStyle($span('O'))->getNumberFormat()->setFormatCode(self::FMT_SCORE);

        $ws->freezePane('D6');
        $this->autoRowHeights($ws, 2, $total);
        $this->pageSetup($ws, repeatRows: [4, 5]);
    }
}
