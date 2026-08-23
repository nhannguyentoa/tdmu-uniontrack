<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['activity_id', 'member_id', 'role_note', 'status', 'registered_at', 'note'])]
class ActivityParticipant extends Model
{
    use HasFactory;

    public const STATUSES = [
        'registered' => 'Đã đăng ký',
        'attended' => 'Đã tham gia',
        'absent' => 'Vắng mặt',
        'cancelled' => 'Đã hủy',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
