<?php

namespace App\Exports\Activities;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Bảng 2 chiều": hàng = hoạt động (gom trùng tên trên toàn trường), cột = tổ công đoàn
 * (mỗi tổ chia Số lần / Điểm đạt), khối Toàn trường ở cuối và các dòng tổng theo tổ.
 */
class MatrixSheet extends ActivitySheet
{
    public const TITLE = 'Bảng 2 chiều';

    protected const FIRST_ROW = 6;

    protected const COUNT_FORMAT = '0;-0;""';

    public function title(): string
    {
        return self::TITLE;
    }

    protected function build(Worksheet $ws): void
    {
        $groups = $this->data->groups()->values();
        $rows = $this->data->matrix();
        $n = $groups->count();
        $countCols = array_map(fn ($i) => self::col(2 + 2 * $i), range(0, max(0, $n - 1)));
        $scoreCols = array_map(fn ($i) => self::col(3 + 2 * $i), range(0, max(0, $n - 1)));
        [$tCount, $tGroups, $tScore] = [self::col(2 + 2 * $n), self::col(3 + 2 * $n), self::col(4 + 2 * $n)];
        $lastGroupCol = self::col(1 + 2 * $n);

        $this->titleRows($ws, 'BẢNG 2 CHIỀU: HOẠT ĐỘNG × TỔ CÔNG ĐOÀN',
            'Hàng: hoạt động (gom trùng tên toàn trường) · Cột: tổ công đoàn · Ô: số lần tổ chức và điểm đạt · '.$this->metaLine($this->meta), $tScore);

        $top = ['Hoạt động \\ Tổ công đoàn'];
        $sub = [null];
        foreach ($groups as $group) {
            array_push($top, $group->name, null);
            array_push($sub, 'Số lần', 'Điểm đạt');
        }
        array_push($top, 'Toàn trường', null, null);
        array_push($sub, 'Số lần', 'Số tổ', 'Điểm đạt');
        $this->writeGrid($ws, [$top, $sub], 4);

        $ws->mergeCells('A4:A5');
        foreach ($countCols as $i => $col) {
            $ws->mergeCells("{$col}4:{$scoreCols[$i]}4");
        }
        $ws->mergeCells("{$tCount}4:{$tScore}4");
        $this->headerStyle($ws, "A4:{$lastGroupCol}5");
        $this->headerStyle($ws, "{$tCount}4:{$tScore}5", self::NAVY_LIGHT);

        $r = self::FIRST_ROW;
        foreach ($rows as $item) {
            $line = [$item['name']];
            foreach ($groups as $group) {
                $cell = $item['cells'][$group->id] ?? null;
                array_push($line, $cell['count'] ?? 0, $cell ? $cell['earned'] : null);
            }
            $line[] = new Formula('=SUM('.implode(',', array_map(fn ($c) => "{$c}{$r}", $countCols)).')');
            $line[] = new Formula("=SUMPRODUCT((MOD(COLUMN(B{$r}:{$lastGroupCol}{$r})-COLUMN(B{$r}),2)=0)*(B{$r}:{$lastGroupCol}{$r}>0))");
            $line[] = new Formula('=SUM('.implode(',', array_map(fn ($c) => "{$c}{$r}", $scoreCols)).')');
            $this->writeGrid($ws, [$line], $r++);
        }

        if ($rows === []) {
            $ws->setCellValue('A'.self::FIRST_ROW, 'Không có hoạt động nào phù hợp bộ lọc.');
            $r++;
        }

        $last = $r - 1;
        $totalRow = $r;
        $distinctRow = $r + 1;

        $ws->setCellValue("A{$totalRow}", 'Tổng theo tổ');
        $ws->setCellValue("A{$distinctRow}", 'Số hoạt động khác nhau đã tổ chức');
        for ($c = 2; $c <= 4 + 2 * $n; $c++) {
            $col = self::col($c);
            $ws->setCellValue("{$col}{$totalRow}", "=SUM({$col}".self::FIRST_ROW.":{$col}{$last})");
        }
        foreach ($countCols as $i => $col) {
            $ws->setCellValue("{$col}{$distinctRow}", "=COUNTIF({$col}".self::FIRST_ROW.":{$col}{$last},\">0\")");
            $ws->mergeCells("{$col}{$distinctRow}:{$scoreCols[$i]}{$distinctRow}");
        }
        $ws->setCellValue("{$tCount}{$distinctRow}", "=COUNTIF({$tCount}".self::FIRST_ROW.":{$tCount}{$last},\">0\")");
        $ws->mergeCells("{$tCount}{$distinctRow}:{$tScore}{$distinctRow}");

        $note = $distinctRow + 2;
        $ws->setCellValue("A{$note}", 'Cách đọc: mỗi hàng là một hoạt động, mỗi cột lớn là một tổ. Ô xanh = tổ đã tổ chức 1 lần; ô đỏ = tổ chức từ 2 lần trở lên (đã gom lại, điểm đạt là tổng các lần). Ô trống = tổ không tổ chức.');
        $ws->getStyle("A{$note}")->getFont()->setItalic(true)->getColor()->setRGB(self::GREY_TEXT);

        // Định dạng
        $ws->getColumnDimension('A')->setWidth(52);
        foreach ($countCols as $i => $col) {
            $ws->getColumnDimension($col)->setWidth(7);
            $ws->getColumnDimension($scoreCols[$i])->setWidth(8);
        }
        $this->widths($ws, [$tCount => 9, $tGroups => 7, $tScore => 9]);

        $first = self::FIRST_ROW;
        $this->borders($ws, "A4:{$tScore}{$distinctRow}");
        $this->align($ws, "B{$first}:{$tScore}{$distinctRow}", Alignment::HORIZONTAL_CENTER);
        $this->align($ws, "A{$first}:A{$distinctRow}", Alignment::HORIZONTAL_LEFT);

        foreach ($countCols as $i => $col) {
            $ws->getStyle("{$col}{$first}:{$col}{$last}")->getNumberFormat()->setFormatCode(self::COUNT_FORMAT);
            $score = $ws->getStyle("{$scoreCols[$i]}{$first}:{$scoreCols[$i]}{$totalRow}");
            $score->getNumberFormat()->setFormatCode(self::FMT_SCORE);
            $ws->getStyle("{$scoreCols[$i]}{$first}:{$scoreCols[$i]}{$last}")->getFont()->getColor()->setRGB(self::GREY_TEXT);
            $ws->getStyle("{$col}{$first}:{$col}{$last}")->setConditionalStyles($this->countRules($col.$first));
        }
        $this->fill($ws, "{$tCount}{$first}:{$tScore}{$last}", self::SUBTOTAL_FILL, true);
        $ws->getStyle("{$tScore}{$first}:{$tScore}{$totalRow}")->getNumberFormat()->setFormatCode(self::FMT_SCORE);
        $this->fill($ws, "A{$totalRow}:{$tScore}{$totalRow}", self::TOTAL_FILL, true);
        $this->fill($ws, "A{$distinctRow}:{$tScore}{$distinctRow}", self::SUBTOTAL_FILL, true);

        $ws->freezePane('B6');
        $this->autoRowHeights($ws, 2, $distinctRow);
        $this->pageSetup($ws, PageSetup::PAPERSIZE_A3, [4, 5]);
    }

    /** Ô Số lần: = 1 nền xanh nhạt; ≥ 2 (đã gom) chữ đỏ đậm trên nền cam nhạt. */
    protected function countRules(string $topLeft): array
    {
        $many = (new Conditional)->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition("{$topLeft}>=2");
        $many->getStyle()->getFont()->setBold(true)->getColor()->setRGB(self::RED);
        $many->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $many->getStyle()->getFill()->getStartColor()->setRGB('FCE4D6');
        $many->getStyle()->getFill()->getEndColor()->setRGB('FCE4D6');

        $once = (new Conditional)->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition("{$topLeft}=1");
        $once->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
        $once->getStyle()->getFill()->getStartColor()->setRGB('E2EFDA');
        $once->getStyle()->getFill()->getEndColor()->setRGB('E2EFDA');

        return [$many, $once];
    }
}
