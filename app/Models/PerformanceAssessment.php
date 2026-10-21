<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'athlete_id', 'recorded_by', 'assessment_date', 'height_cm', 'weight_kg', 'standing_reach_cm', 'vertical_jump_cm', 'spike_reach_cm', 'block_reach_cm', 'agility_seconds', 'coach_notes'])]
class PerformanceAssessment extends Model
{
    protected function casts(): array
    {
        return [
            'assessment_date' => 'date', 'height_cm' => 'float', 'weight_kg' => 'float',
            'standing_reach_cm' => 'float', 'vertical_jump_cm' => 'float',
            'spike_reach_cm' => 'float', 'block_reach_cm' => 'float', 'agility_seconds' => 'float',
        ];
    }

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function athlete(): BelongsTo { return $this->belongsTo(Athlete::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
