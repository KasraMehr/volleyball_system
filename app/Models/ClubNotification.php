<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'user_id', 'sender_id', 'type', 'title', 'message', 'action_url', 'channel', 'delivery_status', 'metadata', 'sent_at', 'read_at'])]
class ClubNotification extends Model
{
    protected function casts(): array { return ['metadata' => 'array', 'sent_at' => 'datetime', 'read_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
}
