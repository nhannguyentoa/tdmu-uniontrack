<?php

namespace App\Exports;

use App\Exports\Members\MemberGroupSheet;
use App\Exports\Members\MemberSummarySheet;
use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * File Excel "Danh sách đoàn viên": mỗi tổ công đoàn một sheet (theo tên tổ A→Z), sheet cuối là "Tổng hợp".
 * Sheet "Tổng hợp" đặt cuối vì chức năng "Nhập từ Excel" đọc sheet đầu tiên.
 */
class MembersExport implements Export, WithMultipleSheets
{
    /**
     * @param  Collection<int, Member>  $members  đoàn viên đã lọc, đã nạp sẵn unionGroup
     * @param  array{exported_at: \DateTimeInterface, exported_by: string, filters: string}  $meta
     */
    public function __construct(protected Collection $members, protected array $meta) {}

    public function sheets(): array
    {
        $groups = $this->members
            ->groupBy('union_group_id')
            ->sortBy(fn (Collection $items) => mb_strtolower($items->first()->unionGroup?->name ?? ''), SORT_NATURAL);

        if ($groups->isEmpty()) {
            return [new MemberGroupSheet('Danh sách đoàn viên', 'DANH SÁCH ĐOÀN VIÊN', collect(), $this->meta,
                'Không có đoàn viên nào phù hợp bộ lọc · Bộ lọc: '.$this->meta['filters'])];
        }

        $used = [mb_strtolower(MemberSummarySheet::TITLE)];
        $sheets = [];
        $summary = [];

        foreach ($groups as $items) {
            $name = $items->first()->unionGroup?->name ?? 'Chưa có tổ';
            $title = MemberGroupSheet::safeTitle($name, $used);
            $sorted = $items->sortBy(fn (Member $m) => mb_strtolower($m->full_name), SORT_NATURAL)->values();

            $sheets[] = new MemberGroupSheet($title, 'DANH SÁCH ĐOÀN VIÊN – '.mb_strtoupper($name), $sorted, $this->meta);
            $summary[] = [
                'name' => $name,
                'sheet' => $title,
                'total' => $items->count(),
                'counts' => collect(array_keys(MemberGroupSheet::STATUSES))
                    ->mapWithKeys(fn ($s) => [$s => $items->where('status', $s)->count()])->all(),
            ];
        }

        $sheets[] = new MemberSummarySheet($summary, $this->meta);

        return $sheets;
    }
}
