<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['activity_id', 'status', 'progress', 'note', 'changed_by'])]
class ActivityStatusHistory extends Model
{
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
