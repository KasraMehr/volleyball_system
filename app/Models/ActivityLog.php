<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'user_id', 'action', 'route_name', 'method', 'subject_type', 'subject_id', 'description', 'ip_address', 'user_agent', 'context', 'occurred_at'])]
class ActivityLog extends Model
{
    protected function casts(): array { return ['context' => 'array', 'occurred_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
}
