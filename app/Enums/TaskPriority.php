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

}
