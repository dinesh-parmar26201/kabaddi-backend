<?php

namespace App\Enums;

enum MatchStatus: string
{
    case UPCOMING = 'upcoming';
    case FIRST_HALF = 'first_half';
    case TIMEOUT = 'timeout';
    case SECOND_HALF = 'second_half';
    case COMPLETED = 'completed';
    case ONGOING = 'ongoing';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
  
    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => 'Upcoming',
            self::FIRST_HALF => '1st Half',
            self::TIMEOUT => 'Timeout',
            self::SECOND_HALF => '2nd Half',
            self::COMPLETED => 'Completed',
            self::ONGOING => 'Ongoing',
        };
    }

    public static function isLive(): array
    {
        return [
            self::FIRST_HALF->value,
            self::SECOND_HALF->value,
            self::TIMEOUT->value,
            self::ONGOING->value,
        ];
    }
}
