<?php

namespace App\Exports\Members;

use App\Exports\Activities\StylesWorkbook;
use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Một sheet danh sách đoàn viên của một tổ công đoàn. Tên cột giữ giống chức năng "Nhập từ Excel"
 * để file xuất ra có thể nhập lại; cũng dùng (không có đoàn viên) làm file mẫu nhập.
 */
class MemberGroupSheet implements FromArray, WithEvents, WithStrictNullComparison, WithTitle
{
    use StylesWorkbook;

    public const HEADER_ROW = 4;

    public const HEADINGS = ['STT', 'Mã đoàn viên', 'Họ và tên', 'Ngày sinh', 'Giới tính', 'Chức vụ', 'Đơn vị',
        'Số điện thoại', 'Email', 'Ngày tham gia', 'Trạng thái', 'Ghi chú'];

    public const GENDERS = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];

    public const STATUSES = ['active' => 'Đang hoạt động', 'inactive' => 'Ngưng hoạt động', 'transferred' => 'Đã chuyển'];

    protected const STATUS_FILLS = ['active' => 'E2EFDA', 'inactive' => 'EDEDED', 'transferred' => 'FFF2CC'];

    /**
     * @param  Collection<int, Member>  $members  đoàn viên của tổ, đã sắp theo họ tên
     * @param  string|null  $subtitle  dòng 2; null thì tự ghi số lượng, giờ xuất và bộ lọc theo $meta
     */
    public function __construct(
        protected string $sheetTitle,
        protected string $heading,
        protected Collection $members,
        protected array $meta = [],
        protected ?string $subtitle = null,
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
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
        $active = $this->members->where('status', 'active')->count();
        $subtitle = $this->subtitle
            ?? "{$this->members->count()} đoàn viên ({$active} đang hoạt động) · ".$this->metaLine($this->meta);
        $this->titleRows($ws, $this->heading, $subtitle, 'L');

        $grid = [self::HEADINGS];
        foreach ($this->members->values() as $i => $m) {
            $grid[] = [
                $i + 1,
                $m->code,
                $m->full_name,
                $m->dob ? Date::dateTimeToExcel($m->dob) : null,
                self::GENDERS[$m->gender] ?? null,
                $m->position,
                $m->department,
                $m->phone,
                $m->email,
                $m->joined_union_date ? Date::dateTimeToExcel($m->joined_union_date) : null,
                self::STATUSES[$m->status] ?? $m->status,
                $m->note,
            ];
        }
        $this->writeGrid($ws, $grid, self::HEADER_ROW);

        $first = self::HEADER_ROW + 1;
        $last = max($first, self::HEADER_ROW + count($grid) - 1);

        $this->widths($ws, ['A' => 7, 'B' => 16, 'C' => 28, 'D' => 12, 'E' => 9, 'F' => 18, 'G' => 22,
            'H' => 14, 'I' => 28, 'J' => 13, 'K' => 16, 'L' => 24]);
        $this->headerStyle($ws, 'A'.self::HEADER_ROW.':L'.self::HEADER_ROW);
        $this->borders($ws, 'A'.self::HEADER_ROW.":L{$last}");
        $this->align($ws, "A{$first}:L{$last}", Alignment::HORIZONTAL_CENTER);
        foreach (['C', 'F', 'G', 'I', 'L'] as $col) {
            $this->align($ws, "{$col}{$first}:{$col}{$last}", Alignment::HORIZONTAL_LEFT);
        }
        $ws->getStyle("D{$first}:D{$last}")->getNumberFormat()->setFormatCode(self::FMT_DATE);
        $ws->getStyle("J{$first}:J{$last}")->getNumberFormat()->setFormatCode(self::FMT_DATE);

        $rules = [];
        foreach (self::STATUS_FILLS as $status => $rgb) {
            $rule = (new Conditional)->setConditionType(Conditional::CONDITION_EXPRESSION)
                ->addCondition('$K'.$first.'="'.self::STATUSES[$status].'"');
            $rule->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $rule->getStyle()->getFill()->getStartColor()->setRGB($rgb);
            $rule->getStyle()->getFill()->getEndColor()->setRGB($rgb);
            $rules[] = $rule;
        }
        $ws->getStyle("K{$first}:K{$last}")->setConditionalStyles($rules);

        $ws->setAutoFilter('A'.self::HEADER_ROW.":L{$last}");
        $ws->freezePane('D'.$first);
        $this->autoRowHeights($ws, 2, $last);
        $this->pageSetup($ws, repeatRows: [self::HEADER_ROW, self::HEADER_ROW]);
    }

    /** Tên sheet hợp lệ của Excel: tối đa 31 ký tự, không chứa []:*?/\ và không trùng các tên đã dùng. */
    public static function safeTitle(string $name, array &$used): string
    {
        $base = trim(mb_substr(preg_replace('/[\[\]:*?\/\\\\]+/u', ' ', $name) ?? $name, 0, 31)) ?: 'Tổ';
        $title = $base;
        for ($i = 2; in_array(mb_strtolower($title), $used, true); $i++) {
            $title = mb_substr($base, 0, 31 - mb_strlen(" ({$i})"))." ({$i})";
        }
        $used[] = mb_strtolower($title);

        return $title;
    }
}
