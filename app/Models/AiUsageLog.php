<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'feature', 'provider', 'model', 'user_id', 'activity_id', 'subject', 'success', 'error',
    'duration_ms', 'input_tokens', 'output_tokens', 'stats',
])]
class AiUsageLog extends Model
{
    public const FEATURE_ATTENDANCE = 'attendance_import';
    public const FEATURE_DOCUMENT = 'document_draft';

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'stats' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
