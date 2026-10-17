<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;

final readonly class DeleteTask
{
    /**
     * Permanently delete the task; its number is not reused.
     */
    public function handle(Task $task): void
    {
        $task->delete();
    }
}
