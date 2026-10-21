<?php

namespace App\Enums;

enum UserRole: string
{
    case ClubAdmin = 'club_admin';
    case Finance = 'finance';
    case Coach = 'coach';
    case Athlete = 'athlete';
    case Guardian = 'guardian';

    public function label(): string
    {
        return match ($this) {
            self::ClubAdmin => 'مدیر باشگاه',
            self::Finance => 'مسئول مالی',
            self::Coach => 'مربی',
            self::Athlete => 'ورزشکار',
            self::Guardian => 'والد یا سرپرست',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ], self::cases());
    }
}
