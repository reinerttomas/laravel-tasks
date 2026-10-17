<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskPriority: string
{
    case None = 'none';
    case Urgent = 'urgent';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    /**
     * Get the priorities from the least to the most urgent.
     *
     * @return list<self>
     */
    public static function byUrgency(): array
    {
        return [self::None, self::Low, self::Medium, self::High, self::Urgent];
    }
}
