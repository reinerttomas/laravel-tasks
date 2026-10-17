<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case CreateProject = 'project:create';
    case UpdateProject = 'project:update';
    /** Also covers restoring an archived project. */
    case ArchiveProject = 'project:archive';
    case DeleteProject = 'project:delete';

    case CreateTask = 'task:create';
    case UpdateTask = 'task:update';
    case DeleteTask = 'task:delete';

    case CreateComment = 'comment:create';
    case DeleteComment = 'comment:delete';
}
