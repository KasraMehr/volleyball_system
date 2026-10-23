<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workout_plan_id', 'day_label', 'title', 'category', 'sets', 'reps', 'duration_minutes', 'rest_seconds', 'notes', 'sort_order'])]
class WorkoutExercise extends Model
{
    public function plan(): BelongsTo { return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id'); }
    public function completions(): HasMany { return $this->hasMany(WorkoutCompletion::class); }
}
