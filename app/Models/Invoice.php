<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['club_id', 'athlete_id', 'fee_plan_id', 'title', 'amount', 'discount', 'due_date', 'status', 'payment_token'])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'discount' => 'integer', 'due_date' => 'date'];
    }

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function athlete(): BelongsTo { return $this->belongsTo(Athlete::class); }
    public function feePlan(): BelongsTo { return $this->belongsTo(FeePlan::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function paidAmount(): int
    {
        return (int) $this->payments()->where('status', 'successful')->sum('amount');
    }

    public function payableAmount(): int
    {
        return max(0, $this->amount - $this->discount);
    }

    public function refreshStatus(): void
    {
        $paid = $this->paidAmount();
        $this->update(['status' => $paid >= $this->payableAmount() ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')]);
    }
}
