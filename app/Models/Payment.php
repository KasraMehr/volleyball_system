<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'invoice_id', 'athlete_id', 'received_by', 'amount', 'method', 'status', 'reference_number', 'paid_at', 'notes'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function athlete(): BelongsTo { return $this->belongsTo(Athlete::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}
