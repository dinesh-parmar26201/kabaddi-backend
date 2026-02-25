<?php

namespace App\Enums;

enum EventType: string
{
    case RAID = 'raid';
    case TACKLE = 'tackle';
    case BONUS = 'bonus';
    case ALL_OUT = 'all_out';
    case SUPER_TACKLE = 'super_tackle';
    case LINEOUT = 'lineout';
    case TECHNICAL_POINT = 'technical_point';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
