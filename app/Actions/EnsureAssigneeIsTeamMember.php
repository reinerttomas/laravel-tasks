<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use Illuminate\Validation\ValidationException;

final readonly class EnsureAssigneeIsTeamMember
{
    /**
     * Reject an assignee who is not a member of the team; nobody (null) is always allowed.
     *
     * @throws ValidationException
     */
    public function handle(Team $team, ?string $assigneeId): void
    {
        if ($assigneeId === null || $team->memberships()->where('user_id', $assigneeId)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'assignee_id' => __('The assignee must be a member of this team.'),
        ]);
    }
}
