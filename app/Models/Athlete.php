<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['club_id', 'user_id', 'membership_number', 'national_code', 'first_name', 'last_name', 'phone', 'photo_path', 'emergency_name', 'emergency_phone', 'birth_date', 'gender', 'position', 'jersey_number', 'medical_notes', 'is_active'])]
class Athlete extends Model
{
    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_active' => 'boolean'];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_memberships')
            ->withPivot(['joined_at', 'left_at', 'is_active'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'athlete_guardians', 'athlete_id', 'guardian_id')
            ->withPivot(['relationship', 'is_primary'])->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function performanceAssessments(): HasMany
    {
        return $this->hasMany(PerformanceAssessment::class);
    }

    public function latestAssessment(): HasOne
    {
        return $this->hasOne(PerformanceAssessment::class)->latestOfMany('assessment_date');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::ClubAdmin, UserRole::Finance => $query->where('club_id', $user->club_id),
            UserRole::Coach => $query->whereHas('teams.coaches', fn (Builder $coachQuery) => $coachQuery->whereKey($user->id)),
            UserRole::Athlete => $query->where('user_id', $user->id),
            UserRole::Guardian => $query->whereHas('guardians', fn (Builder $guardianQuery) => $guardianQuery->whereKey($user->id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
