<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $report)
    {
    }

    public function array(): array
    {
        return $this->report['activities']->map(function (Activity $activity) {
            return [
                $activity->code,
                $activity->name,
                $activity->unionGroup?->name,
                $activity->activityType?->name,
                $activity->start_time?->format('d/m/Y H:i'),
                $activity->end_time?->format('d/m/Y H:i'),
                Activity::STATUSES[$activity->status] ?? $activity->status,
                $activity->progress.'%',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            ['TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT'],
            ['BÁO CÁO HOẠT ĐỘNG CÔNG ĐOÀN — '.mb_strtoupper($this->report['label'])],
            ['Tổng số hoạt động: '.$this->report['total_activities'].' | Hoàn thành: '.$this->report['completed_activities'].' | Tỷ lệ hoàn thành: '.$this->report['completion_rate'].'% | Lượt tham gia: '.$this->report['total_participants']],
            [],
            ['Mã HĐ', 'Tên hoạt động', 'Tổ công đoàn', 'Loại hoạt động', 'Bắt đầu', 'Kết thúc', 'Trạng thái', 'Tiến độ'],
        ];
    }

    public function title(): string
    {
        return 'Báo cáo';
    }
}
