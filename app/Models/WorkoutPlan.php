<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['club_id', 'coach_id', 'team_id', 'athlete_id', 'title', 'description', 'starts_on', 'ends_on', 'status'])]
class WorkoutPlan extends Model
{
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date']; }
    public function coach(): BelongsTo { return $this->belongsTo(User::class, 'coach_id'); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function athlete(): BelongsTo { return $this->belongsTo(Athlete::class); }
    public function exercises(): HasMany { return $this->hasMany(WorkoutExercise::class)->orderBy('sort_order'); }
}
