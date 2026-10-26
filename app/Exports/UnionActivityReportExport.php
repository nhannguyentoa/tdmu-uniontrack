<?php

namespace App\Exports;

use App\Models\Activity;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Xuất danh sách hoạt động theo đúng mẫu báo cáo công đoàn cấp trên đang dùng thủ công
 * (khối tiêu đề LĐLĐ/CĐCS, gom nhóm theo Loại hoạt động, cột STT/HOẠT ĐỘNG/MÔ TẢ/THỜI GIAN/MINH CHỨNG).
 * Toàn bộ nội dung được ghi trực tiếp trong sự kiện AfterSheet nên FromArray chỉ trả về mảng rỗng.
 */
class UnionActivityReportExport implements FromArray, WithEvents, WithTitle, WithStrictNullComparison
{
    private const FONT = 'Times New Roman';

    /**
     * @param  Collection<string, Collection<int, Activity>>  $groups  Tên Loại hoạt động => các hoạt động, đã sắp theo thời gian
     */
    public function __construct(protected string $academicYear, protected Collection $groups) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Hoạt động công đoàn';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->build($event->sheet->getDelegate());
            },
        ];
    }

    protected function build(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(6.5);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(17);
        $sheet->getColumnDimension('E')->setWidth(37);

        $sheet->setCellValue('A1', 'LIÊN ĐOÀN LAO ĐỘNG TP HỒ CHÍ MINH');
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('D1', 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM');
        $sheet->mergeCells('D1:E1');

        $sheet->setCellValue('A2', 'CĐCS TRƯỜNG ĐH THỦ DẦU MỘT');
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('D2', 'Độc lập - Tự do - Hạnh phúc');
        $sheet->mergeCells('D2:E2');

        foreach (['A1', 'D1', 'A2', 'D2'] as $addr) {
            $sheet->getStyle($addr)->getFont()->setName(self::FONT)->setSize(14)->setBold(true);
        }
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A4', 'HOẠT ĐỘNG CÔNG ĐOÀN');
        $sheet->mergeCells('A4:E4');
        $sheet->setCellValue('A5', 'NĂM HỌC '.$this->academicYear);
        $sheet->mergeCells('A5:E5');
        $sheet->getStyle('A4:A5')->getFont()->setName(self::FONT)->setSize(16)->setBold(true);
        $sheet->getStyle('A4:A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = 7;
        foreach (['A', 'B', 'C', 'D', 'E'] as $i => $col) {
            $label = ['STT', 'HOẠT ĐỘNG', 'MÔ TẢ', 'THỜI GIAN', 'MINH CHỨNG'][$i];
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }
        $sheet->getRowDimension($headerRow)->setRowHeight(34.5);

        $row = $headerRow + 1;
        $stt = 1;
        $groupRows = [];
        foreach ($this->groups as $groupName => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $groupRows[] = $row;
            $sheet->setCellValue("A{$row}", $groupName);
            $row++;

            foreach ($activities as $activity) {
                $sheet->setCellValue("A{$row}", $stt);
                $sheet->setCellValue("B{$row}", $activity->name);
                $sheet->setCellValue("C{$row}", $this->description($activity));
                $sheet->setCellValue("D{$row}", $this->timeRange($activity));
                $sheet->setCellValue("E{$row}", $this->evidence($activity));
                $stt++;
                $row++;
            }
        }
        $lastRow = max($row - 1, $headerRow);

        $range = "A{$headerRow}:E{$lastRow}";
        $sheet->getStyle($range)->getFont()->setName(self::FONT)->setSize(13);
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.($headerRow + 1).":A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.($headerRow + 1).":D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Dòng tên nhóm (Loại hoạt động): gộp A:E để tên nhóm có đủ chỗ hiển thị thay vì bị bó hẹp
        // và xuống dòng trong cột A rộng 6.5 (cột STT) như khi áp định dạng chung ở trên.
        foreach ($groupRows as $groupRow) {
            $sheet->mergeCells("A{$groupRow}:E{$groupRow}");
            $sheet->getStyle("A{$groupRow}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$groupRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(false);
        }
    }

    private function description(Activity $activity): string
    {
        return trim((string) ($activity->content ?: $activity->goal));
    }

    private function timeRange(Activity $activity): string
    {
        if (! $activity->start_time) {
            return '';
        }

        if (! $activity->end_time || $activity->start_time->isSameDay($activity->end_time)) {
            return $activity->start_time->format('d/m/Y');
        }

        return $activity->start_time->format('d/m/Y').' - '.$activity->end_time->format('d/m/Y');
    }

    private function evidence(Activity $activity): string
    {
        return $activity->evidences->pluck('file_name')->implode("\n");
    }
}
