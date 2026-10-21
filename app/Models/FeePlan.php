<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['club_id', 'team_id', 'title', 'amount', 'frequency', 'is_active'])]
class FeePlan extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'is_active' => 'boolean'];
    }

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
}
