<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['club_id', 'name', 'email', 'phone', 'role', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function assignedTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'coach_assignments', 'coach_id', 'team_id')
            ->withPivot(['is_head_coach', 'active_from', 'active_until'])
            ->withTimestamps();
    }

    public function athleteProfile(): HasOne
    {
        return $this->hasOne(Athlete::class);
    }

    public function guardianAthletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'athlete_guardians', 'guardian_id', 'athlete_id')
            ->withPivot(['relationship', 'is_primary'])->withTimestamps();
    }

    public function clubNotifications(): HasMany { return $this->hasMany(ClubNotification::class); }

    public function hasRole(UserRole|string ...$roles): bool
    {
        return collect($roles)->contains(fn (UserRole|string $role) => $this->role->value === ($role instanceof UserRole ? $role->value : $role));
    }
}
