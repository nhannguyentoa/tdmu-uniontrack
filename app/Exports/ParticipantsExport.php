<?php

namespace App\Exports;

use App\Models\ActivityParticipant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ParticipantsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
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
}
