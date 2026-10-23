<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workout_exercise_id', 'athlete_id', 'completed_at', 'effort', 'note'])]
class WorkoutCompletion extends Model
{
    protected function casts(): array { return ['completed_at' => 'datetime']; }
    public function exercise(): BelongsTo { return $this->belongsTo(WorkoutExercise::class, 'workout_exercise_id'); }
    public function athlete(): BelongsTo { return $this->belongsTo(Athlete::class); }
}
