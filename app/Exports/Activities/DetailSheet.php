<?php

namespace App\Exports\Activities;

use App\Models\Activity;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Dữ liệu chi tiết": mỗi hoạt động một dòng, có bộ lọc, tô màu trạng thái và cột cảnh báo dữ liệu.
 */
class DetailSheet extends ActivitySheet
{
    public const TITLE = 'Dữ liệu chi tiết';

    protected const STATUS_FILLS = [
        Activity::STATUS_COMPLETED => 'E2EFDA',
        Activity::STATUS_IN_PROGRESS => 'DDEBF7',
        Activity::STATUS_PREPARING => 'FFF2CC',
        Activity::STATUS_NOT_STARTED => 'EDEDED',
        Activity::STATUS_CANCELLED => 'F8CBAD',
    ];

    public function title(): string
    {
        return self::TITLE;
    }

    protected function build(Worksheet $ws): void
    {
        $grid = [[
            'Mã hoạt động', 'Tên hoạt động', 'Tổ công đoàn', 'Loại hoạt động', 'Bắt đầu', 'Kết thúc', 'Địa điểm', 'Trạng thái',
            'Tiến độ', 'SL dự kiến', 'SL thực tế', 'Tính thi đua', 'Điểm tối đa', 'Điểm đạt', 'Cảnh báo dữ liệu',
        ]];

        foreach ($this->data->details() as $a) {
            $grid[] = [
                $a->code,
                $a->name,
                $a->unionGroup?->name,
                $a->activityType?->name,
                $a->start_time ? Date::dateTimeToExcel($a->start_time) : null,
                $a->end_time ? Date::dateTimeToExcel($a->end_time) : null,
                $a->location,
                $a->statusLabel(),
                $a->progress === null ? null : $a->progress / 100,
                $a->expected_quantity,
                $a->actual_quantity,
                $a->counts_for_evaluation ? 'Có' : 'Không',
                $a->counts_for_evaluation ? (float) $a->evaluation_max_score : null,
                $a->counts_for_evaluation ? $a->earnedEvaluationScore() : null,
                implode('; ', ActivityAggregator::warnings($a)) ?: null,
            ];
        }

        $this->writeGrid($ws, $grid);
        $last = max(2, count($grid));

        $this->widths($ws, [
            'A' => 15, 'B' => 52, 'C' => 26, 'D' => 31, 'E' => 17, 'F' => 17, 'G' => 22, 'H' => 16,
            'I' => 9, 'J' => 10, 'K' => 10, 'L' => 10, 'M' => 10, 'N' => 10, 'O' => 44,
        ]);

        $this->headerStyle($ws, 'A1:O1');
        $this->borders($ws, "A1:O{$last}");
        $this->align($ws, "A2:O{$last}", Alignment::HORIZONTAL_CENTER);
        foreach (['B', 'C', 'D', 'G', 'O'] as $col) {
            $this->align($ws, "{$col}2:{$col}{$last}", Alignment::HORIZONTAL_LEFT);
        }

        $ws->getStyle("E2:F{$last}")->getNumberFormat()->setFormatCode(self::FMT_DATETIME);
        $ws->getStyle("I2:I{$last}")->getNumberFormat()->setFormatCode(self::FMT_PCT);
        $ws->getStyle("J2:K{$last}")->getNumberFormat()->setFormatCode(self::FMT_QTY);
        $ws->getStyle("M2:N{$last}")->getNumberFormat()->setFormatCode(self::FMT_SCORE);
        $ws->getStyle("O2:O{$last}")->getFont()->getColor()->setRGB(self::RED);

        $conditions = [];
        foreach (self::STATUS_FILLS as $status => $rgb) {
            $rule = (new Conditional)->setConditionType(Conditional::CONDITION_EXPRESSION)
                ->addCondition('$H2="'.Activity::STATUSES[$status].'"');
            $rule->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $rule->getStyle()->getFill()->getStartColor()->setRGB($rgb);
            $rule->getStyle()->getFill()->getEndColor()->setRGB($rgb);
            $conditions[] = $rule;
        }
        $ws->getStyle("H2:H{$last}")->setConditionalStyles($conditions);

        $ws->setAutoFilter("A1:O{$last}");
        $ws->freezePane('C2');
        $this->autoRowHeights($ws, 1, $last);
        $this->pageSetup($ws, repeatRows: [1, 1]);
    }
}
