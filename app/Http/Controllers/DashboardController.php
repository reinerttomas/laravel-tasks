<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CountTeamTasksByStatus;
use App\Actions\GroupOpenTasks;
use App\Data\TaskRow;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    /**
     * Display the team's task counts and the user's open tasks, grouped by what needs attention first.
     */
    public function __invoke(Request $request, Team $currentTeam, CountTeamTasksByStatus $countTeamTasksByStatus, GroupOpenTasks $groupOpenTasks): Response
    {
        $email = mb_strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->pending()
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'teamTaskCounts' => $countTeamTasksByStatus->handle($currentTeam),
            'myTasks' => array_map(
                fn (Collection $tasks): array => $tasks->map(TaskRow::fromTask(...))->all(),
                $groupOpenTasks->handle($currentTeam, $request->user()),
            ),
        ]);
    }
}
