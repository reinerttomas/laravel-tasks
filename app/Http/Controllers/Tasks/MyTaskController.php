<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Actions\CountTaskFacets;
use App\Actions\ListTasks;
use App\Data\TaskPage;
use App\Enums\TaskStatus;
use App\Http\Requests\Tasks\TaskTableRequest;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

final class MyTaskController
{
    /**
     * Display the tasks assigned to the user across the team's active projects, open ones by default.
     */
    public function __invoke(TaskTableRequest $request, Team $currentTeam, ListTasks $listTasks, CountTaskFacets $countTaskFacets): Response
    {
        $filters = $request->filters(defaultStatuses: TaskStatus::open());
        $tasks = Task::query()
            ->whereBelongsTo($request->user(), 'assignee')
            ->whereHas('project', fn (Builder $query) => $query->whereBelongsTo($currentTeam)->active());

        return Inertia::render('my-tasks', [
            'tasks' => TaskPage::fromPaginator($listTasks->handle($tasks, $filters, $request->integer('page', 1))),
            'facets' => $countTaskFacets->handle($tasks, $filters, ['status', 'priority', 'project']),
            'filters' => $filters->toProps(),
        ]);
    }
}
