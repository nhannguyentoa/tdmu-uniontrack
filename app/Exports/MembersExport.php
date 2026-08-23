<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MembersExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(protected Collection $members)
    {
    }

    public function collection(): Collection
    {
        return $this->members;
    }

    public function headings(): array
    {
        return ['Mã đoàn viên', 'Họ và tên', 'Ngày sinh', 'Giới tính', 'Email', 'Số điện thoại', 'Chức vụ', 'Đơn vị', 'Tổ công đoàn', 'Ngày tham gia', 'Trạng thái'];
    }

    public function map($member): array
    {
        $genderMap = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
        $statusMap = ['active' => 'Đang hoạt động', 'inactive' => 'Ngưng hoạt động', 'transferred' => 'Đã chuyển'];

        return [
            $member->code,
            $member->full_name,
            $member->dob?->format('d/m/Y'),
            $genderMap[$member->gender] ?? '',
            $member->email,
            $member->phone,
            $member->position,
            $member->department,
            $member->unionGroup?->name,
            $member->joined_union_date?->format('d/m/Y'),
            $statusMap[$member->status] ?? $member->status,
        ];
    }

    public function title(): string
    {
        return 'Danh sách đoàn viên';
    }
}
