<?php

namespace App\Exports\Activities;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\WithCharts;
use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Tổng hợp": mỗi tổ một dòng (đếm theo trạng thái, tỷ lệ hoàn thành, hạng) kèm biểu đồ tỷ lệ hoàn thành.
 */
class SummarySheet extends ActivitySheet implements WithCharts
{
    public const TITLE = 'Tổng hợp';

    protected const FIRST_ROW = 4;

    /** Thứ tự cột trạng thái D..H. */
    protected const STATUS_ORDER = [
        Activity::STATUS_COMPLETED, Activity::STATUS_IN_PROGRESS, Activity::STATUS_PREPARING,
        Activity::STATUS_NOT_STARTED, Activity::STATUS_CANCELLED,
    ];

    public function title(): string
    {
        return self::TITLE;
    }

    protected function lastRow(): int
    {
        return self::FIRST_ROW + max(1, count($this->data->summary())) - 1;
    }

    protected function build(Worksheet $ws): void
    {
        $this->titleRows($ws, 'TỔNG HỢP HOẠT ĐỘNG THEO TỔ CÔNG ĐOÀN', $this->metaLine($this->meta), 'M');

        $header = ['Tổ công đoàn', 'Tổng lượt HĐ', 'Số HĐ (sau gom)'];
        foreach (self::STATUS_ORDER as $status) {
            $header[] = Activity::STATUSES[$status];
        }
        array_push($header, 'Tỷ lệ hoàn thành', 'SL dự kiến', 'SL thực tế', 'Điểm đạt', 'Hạng');
        $this->writeGrid($ws, [$header], 3);
        $this->headerStyle($ws, 'A3:M3');

        $first = self::FIRST_ROW;
        $last = $this->lastRow();
        $grid = [];
        foreach ($this->data->summary() as $i => $item) {
            $r = $first + $i;
            $line = [$item['group']->name, $item['total'], $item['distinct']];
            foreach (self::STATUS_ORDER as $status) {
                $line[] = $item['statuses'][$status];
            }
            array_push($line,
                new Formula("=IFERROR(D{$r}/(B{$r}-H{$r}),0)"),
                $item['expected'], $item['actual'], $item['earned'],
                new Formula("=RANK(I{$r},\$I\${$first}:\$I\${$last})"),
            );
            $grid[] = $line;
        }
        $this->writeGrid($ws, $grid, $first);

        $total = $last + 1;
        $ws->setCellValue("A{$total}", 'TỔNG CỘNG');
        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L'] as $col) {
            $ws->setCellValue("{$col}{$total}", "=SUM({$col}{$first}:{$col}{$last})");
        }
        $ws->setCellValue("I{$total}", "=IFERROR(D{$total}/(B{$total}-H{$total}),0)");

        $this->widths($ws, ['A' => 30, 'B' => 10, 'C' => 11, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12, 'H' => 10,
            'I' => 12, 'J' => 11, 'K' => 11, 'L' => 10, 'M' => 8]);
        $this->borders($ws, "A3:M{$total}");
        $this->align($ws, "B{$first}:M{$total}", Alignment::HORIZONTAL_CENTER);
        $this->align($ws, "A{$first}:A{$last}", Alignment::HORIZONTAL_LEFT);
        $this->fill($ws, "A{$total}:M{$total}", self::TOTAL_FILL, true);
        $ws->getStyle("A{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("I{$first}:I{$total}")->getNumberFormat()->setFormatCode(self::FMT_PCT);
        $ws->getStyle("J{$first}:K{$total}")->getNumberFormat()->setFormatCode(self::FMT_QTY);
        $ws->getStyle("L{$first}:L{$total}")->getNumberFormat()->setFormatCode(self::FMT_SCORE);

        $ws->freezePane('B4');
        $this->autoRowHeights($ws, 2, $total);
        $this->pageSetup($ws, repeatRows: [3, 3], fitToHeight: 1);
    }

    /** Biểu đồ thanh ngang đặt dưới bảng; mỗi tổ ~3 dòng chiều cao để hiện đủ nhãn. */
    public function charts(): Chart
    {
        $first = self::FIRST_ROW;
        $last = $this->lastRow();
        $sheet = "'".self::TITLE."'";

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheet}!\$I\$3", null, 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheet}!\$A\${$first}:\$A\${$last}", null, $last - $first + 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheet}!\$I\${$first}:\$I\${$last}", self::FMT_PCT, $last - $first + 1)],
        );
        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $valueAxis = new Axis;
        $valueAxis->setAxisNumberProperties(self::FMT_PCT);

        $chart = new Chart('ty-le-hoan-thanh', new Title('Tỷ lệ hoàn thành theo tổ'), null, new PlotArea(null, [$series]),
            true, DataSeries::EMPTY_AS_GAP, null, null, null, $valueAxis);

        $top = $last + 3;
        $chart->setTopLeftPosition("A{$top}");
        $chart->setBottomRightPosition('H'.($top + max(15, 3 * ($last - $first + 1))));

        return $chart;
    }
}
