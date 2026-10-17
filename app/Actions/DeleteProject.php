<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

final readonly class DeleteProject
{
    /**
     * Permanently delete the project; its tasks and comments go with it once they exist.
     */
    public function handle(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            $project->delete();
        });
    }
}
