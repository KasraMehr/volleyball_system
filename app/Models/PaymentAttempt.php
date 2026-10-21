<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'invoice_id', 'gateway', 'authority', 'amount', 'status', 'reference_number', 'callback_url', 'metadata', 'expires_at', 'completed_at'])]
class PaymentAttempt extends Model
{
    protected function casts(): array { return ['metadata' => 'array', 'expires_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
}
