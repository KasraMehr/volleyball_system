<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['club_id', 'name', 'code', 'gender', 'age_group', 'color', 'capacity', 'is_active'])]
class Team extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coach_assignments', 'team_id', 'coach_id')
            ->withPivot(['is_head_coach', 'active_from', 'active_until'])
            ->withTimestamps();
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'team_memberships')
            ->withPivot(['joined_at', 'left_at', 'is_active'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function feePlans(): HasMany
    {
        return $this->hasMany(FeePlan::class);
    }
}
