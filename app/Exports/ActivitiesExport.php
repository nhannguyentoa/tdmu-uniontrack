<?php

namespace App\Exports;

use App\Exports\Activities\ActivityAggregator;
use App\Exports\Activities\ByGroupSheet;
use App\Exports\Activities\DetailSheet;
use App\Exports\Activities\MatrixSheet;
use App\Exports\Activities\NotesSheet;
use App\Exports\Activities\SummarySheet;
use App\Models\Activity;
use App\Models\UnionGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * File Excel của nút "Xuất Excel" ở trang Danh sách hoạt động, gồm 5 sheet:
 * Bảng 2 chiều (hoạt động × tổ), Theo tổ công đoàn, Tổng hợp, Dữ liệu chi tiết và Ghi chú.
 */
class ActivitiesExport implements Export, WithMultipleSheets
{
    /**
     * @param  Collection<int, Activity>  $activities  các hoạt động đã lọc như trên trang danh sách
     * @param  Collection<int, UnionGroup>  $groups  các tổ hiển thị, đã sắp theo tên
     * @param  array{exported_at: \DateTimeInterface, exported_by: string, filters: string}  $meta
     */
    public function __construct(protected Collection $activities, protected Collection $groups, protected array $meta) {}

    public function sheets(): array
    {
        $data = new ActivityAggregator($this->activities, $this->groups);

        return [
            new MatrixSheet($data, $this->meta),
            new ByGroupSheet($data, $this->meta),
            new SummarySheet($data, $this->meta),
            new DetailSheet($data, $this->meta),
            new NotesSheet($data, $this->meta),
        ];
    }
}
