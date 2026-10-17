<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

final readonly class DeleteTask
{
    /**
     * Permanently delete the task with its comments; its number is not reused.
     */
    public function handle(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $task->comments()->delete();
            $task->delete();
        });
    }
}
