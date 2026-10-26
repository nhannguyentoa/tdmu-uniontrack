<?php

namespace App\Exports\Activities;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Màu, font, cách ghi ô và thiết lập in dùng chung cho các sheet của file "Danh sách hoạt động".
 */
trait StylesWorkbook
{
    protected const FONT = 'Times New Roman';

    protected const NAVY = '1F4E78';

    protected const NAVY_LIGHT = '2F5597';

    protected const GREY_TEXT = '595959';

    protected const BORDER = '808080';

    protected const SUBTOTAL_FILL = 'F2F2F2';

    protected const TOTAL_FILL = 'BDD7EE';

    protected const GROUP_FILL = 'DDEBF7';

    protected const RED = 'C00000';

    public const FMT_QTY = '#,##0';

    public const FMT_PCT = '0%';

    public const FMT_SCORE = '0.00';

    public const FMT_DATE = 'dd/mm/yyyy';

    public const FMT_DATETIME = 'dd/mm/yyyy hh:mm';

    /** @param  array{exported_at: \DateTimeInterface, exported_by: string, filters: string}  $meta */
    protected function metaLine(array $meta): string
    {
        return 'Dữ liệu xuất lúc '.$meta['exported_at']->format('d/m/Y H:i').' · Bộ lọc: '.$meta['filters'];
    }

    protected static function col(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    /**
     * Ghi lưới ô bắt đầu từ $startRow (cột A). null = bỏ trống, Formula = công thức, chuỗi luôn ghi dạng văn bản.
     *
     * @param  list<list<mixed>>  $grid
     */
    protected function writeGrid(Worksheet $ws, array $grid, int $startRow = 1): void
    {
        foreach ($grid as $r => $cells) {
            foreach ($cells as $c => $value) {
                if ($value === null) {
                    continue;
                }

                $coordinate = self::col($c + 1).($startRow + $r);

                match (true) {
                    $value instanceof Formula => $ws->setCellValue($coordinate, $value->expression),
                    is_string($value) => $ws->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING),
                    default => $ws->setCellValue($coordinate, $value),
                };
            }
        }
    }

    protected function baseStyle(Worksheet $ws): void
    {
        $ws->getParent()->getDefaultStyle()->getFont()->setName(self::FONT)->setSize(11);
        $ws->getParent()->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    /** Dòng 1: tiêu đề lớn; dòng 2: ghi chú nghiêng (cách đọc, thời điểm xuất, bộ lọc). */
    protected function titleRows(Worksheet $ws, string $title, string $subtitle, string $lastCol): void
    {
        $ws->setCellValueExplicit('A1', $title, DataType::TYPE_STRING);
        $ws->setCellValueExplicit('A2', $subtitle, DataType::TYPE_STRING);
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->mergeCells("A2:{$lastCol}2");
        $ws->getStyle('A1')->getFont()->setSize(15)->setBold(true)->getColor()->setRGB(self::NAVY);
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::GREY_TEXT);
        $ws->getStyle("A1:{$lastCol}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $ws->getRowDimension(1)->setRowHeight(30);
    }

    protected function headerStyle(Worksheet $ws, string $range, string $fill = self::NAVY): void
    {
        $style = $ws->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }

    protected function fill(Worksheet $ws, string $range, string $rgb, bool $bold = false): void
    {
        $style = $ws->getStyle($range);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
        if ($bold) {
            $style->getFont()->setBold(true);
        }
    }

    protected function borders(Worksheet $ws, string $range): void
    {
        $ws->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER);
    }

    protected function align(Worksheet $ws, string $range, string $horizontal, bool $wrap = true): void
    {
        $ws->getStyle($range)->getAlignment()->setHorizontal($horizontal)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText($wrap);
    }

    /** @param  array<string, float>  $widths */
    protected function widths(Worksheet $ws, array $widths): void
    {
        foreach ($widths as $col => $width) {
            $ws->getColumnDimension($col)->setWidth($width);
        }
    }

    /** In ngang, vừa 1 trang theo chiều ngang, lặp dòng tiêu đề, chân trang "Trang x / y". */
    protected function pageSetup(Worksheet $ws, string $paper = PageSetup::PAPERSIZE_A4, ?array $repeatRows = null, int $fitToHeight = 0): void
    {
        $setup = $ws->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize($paper);
        $setup->setFitToWidth(1)->setFitToHeight($fitToHeight);
        $setup->setFitToPage(true);

        if ($repeatRows) {
            $setup->setRowsToRepeatAtTopByStartAndEnd($repeatRows[0], $repeatRows[1]);
        }

        $ws->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.6);
        $ws->getHeaderFooter()->setOddFooter('&CTrang &P / &N');
    }

    /**
     * Excel không tự giãn chiều cao dòng cho file sinh bằng code, nên ước lượng số dòng chữ của từng ô
     * (mô phỏng ngắt theo từ, mỗi "\n" là một dòng mới) và đặt chiều cao = 16.5 × số dòng + 4.
     */
    protected function autoRowHeights(Worksheet $ws, int $fromRow, int $toRow, float $minHeight = 20.25): void
    {
        $spans = [];
        foreach ($ws->getMergeCells() as $range) {
            [[$c1, $r1], [$c2, $r2]] = Coordinate::rangeBoundaries($range);
            $spans[self::col($c1).$r1] = [$c1, $c2, $r1 !== $r2];
            for ($r = $r1; $r <= $r2; $r++) {
                for ($c = $c1; $c <= $c2; $c++) {
                    if ($c !== $c1 || $r !== $r1) {
                        $spans[self::col($c).$r] = 'covered';
                    }
                }
            }
        }

        $lastCol = Coordinate::columnIndexFromString($ws->getHighestColumn());

        for ($row = $fromRow; $row <= $toRow; $row++) {
            $lines = 1;

            for ($c = 1; $c <= $lastCol; $c++) {
                $coordinate = self::col($c).$row;
                $span = $spans[$coordinate] ?? null;

                if ($span === 'covered' || (is_array($span) && $span[2])) {
                    continue;
                }

                $cell = $ws->getCell($coordinate);
                $value = $cell->getValue();
                if (! is_string($value) || $value === '' || $cell->getDataType() === DataType::TYPE_FORMULA) {
                    continue;
                }

                $width = 0;
                foreach (range($c, is_array($span) ? $span[1] : $c) as $i) {
                    $w = $ws->getColumnDimension(self::col($i))->getWidth();
                    $width += $w > 0 ? $w : 9.14;
                }

                $font = $cell->getStyle()->getFont();
                $perLine = max(1, $width * 1.0 * ($font->getBold() ? 0.92 : 1) * 11 / ($font->getSize() ?: 11));
                $lines = max($lines, self::countLines($value, $perLine));
            }

            $ws->getRowDimension($row)->setRowHeight(max($minHeight, 16.5 * $lines + 4));
        }
    }

    protected static function countLines(string $text, float $perLine): int
    {
        $lines = 0;

        foreach (explode("\n", $text) as $paragraph) {
            $current = 0;
            $lines++;

            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                $len = mb_strlen($word);
                if ($current > 0 && $current + 1 + $len > $perLine) {
                    $lines++;
                    $current = $len;
                } else {
                    $current += ($current > 0 ? 1 : 0) + $len;
                }
            }
        }

        return max(1, $lines);
    }
}
