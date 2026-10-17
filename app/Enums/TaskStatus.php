<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Canceled = 'canceled';

    /**
     * Get the statuses of work not yet finished.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Backlog, self::Todo, self::InProgress];
    }
}
