<?php

namespace App\Exports;

use App\Models\ActivityParticipant;
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

class ParticipantsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(protected Collection $participants)
    {
    }

    public function collection(): Collection
    {
        return $this->participants;
    }

    public function headings(): array
    {
        return ['Mã đoàn viên', 'Họ và tên', 'Tổ công đoàn', 'Vai trò/Ghi chú', 'Trạng thái', 'Thời gian đăng ký'];
    }

    public function map($participant): array
    {
        return [
            $participant->member?->code,
            $participant->member?->full_name,
            $participant->member?->unionGroup?->name,
            $participant->role_note,
            ActivityParticipant::STATUSES[$participant->status] ?? $participant->status,
            $participant->registered_at?->format('d/m/Y H:i'),
        ];
    }

    public function title(): string
    {
        return 'Người tham gia';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:F{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:F'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 26, 'C' => 20, 'D' => 24, 'E' => 16, 'F' => 18];
    }
}
