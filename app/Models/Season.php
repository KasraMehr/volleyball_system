<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'title', 'starts_on', 'ends_on', 'is_active'])]
class Season extends Model
{
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean']; }
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
}
