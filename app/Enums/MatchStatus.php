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

    public function isLive(): bool
    {
        return in_array($this, [
            self::FIRST_HALF,
            self::SECOND_HALF,
            self::TIMEOUT,
            self::ONGOING,
        ]);
    }
}
