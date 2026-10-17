<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Team;

final readonly class CountTeamTasksByStatus
{
    public function __construct(private CountTasksByStatus $countTasksByStatus) {}

    /**
     * Count the tasks of the team's active projects per status, with zeros for statuses without tasks.
     *
     * @return array<string, int> counts keyed by status value
     */
    public function handle(Team $team): array
    {
        $countsByProject = $this->countTasksByStatus->handle($team->projects()->active()->get(['id']));

        return collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => array_sum(array_column($countsByProject, $status->value)),
            ])
            ->all();
    }
}
