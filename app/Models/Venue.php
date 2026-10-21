<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'name', 'address', 'capacity', 'floor_type', 'is_active'])]
class Venue extends Model
{
    protected function casts(): array { return ['capacity' => 'integer', 'is_active' => 'boolean']; }
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
}
