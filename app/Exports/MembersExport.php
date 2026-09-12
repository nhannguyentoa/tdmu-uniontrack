<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MembersExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
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

    public function styles(Worksheet $sheet): ?array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:K{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:D'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J2:K'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 26, 'C' => 14, 'D' => 12,
            'E' => 26, 'F' => 16, 'G' => 18, 'H' => 22,
            'I' => 20, 'J' => 14, 'K' => 16,
        ];
    }
}
