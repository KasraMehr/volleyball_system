<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'province', 'city', 'phone', 'email', 'website', 'registration_number', 'address', 'status'])]
class Club extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function venues(): HasMany { return $this->hasMany(Venue::class); }
    public function seasons(): HasMany { return $this->hasMany(Season::class); }
}
