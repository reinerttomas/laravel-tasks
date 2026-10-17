<?php

declare(strict_types=1);

namespace App\Data;

final readonly class TeamPermissions
{
    public function __construct(
        public bool $canUpdateTeam,
        public bool $canDeleteTeam,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
        public bool $canCreateProject,
        public bool $canUpdateProject,
        public bool $canArchiveProject,
        public bool $canDeleteProject,
        public bool $canCreateTask,
        public bool $canUpdateTask,
        public bool $canDeleteTask,
        public bool $canCreateComment,
        public bool $canDeleteComment,
    ) {
        //
    }
}
