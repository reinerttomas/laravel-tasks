import { usePage } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    MoreHorizontal,
    Pencil,
    Trash2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Project } from '@/types';

type Props = {
    project: Project;
    onEdit: (project: Project) => void;
    onArchive: (project: Project) => void;
    onDelete: (project: Project) => void;
};

export default function ProjectActionsMenu({
    project,
    onEdit,
    onArchive,
    onDelete,
}: Props) {
    const permissions = usePage().props.currentTeamPermissions;
    const archived = project.archivedAt !== null;
    // Archived projects are read-only, so only restore and delete remain.
    const canEdit = !archived && (permissions?.canUpdateProject ?? false);
    const canArchive = permissions?.canArchiveProject ?? false;
    const canDelete = permissions?.canDeleteProject ?? false;

    if (!canEdit && !canArchive && !canDelete) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8"
                    aria-label={`Actions for ${project.name}`}
                    data-test="project-actions"
                >
                    <MoreHorizontal />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {canEdit && (
                    <DropdownMenuItem onSelect={() => onEdit(project)}>
                        <Pencil /> Edit
                    </DropdownMenuItem>
                )}
                {canArchive && (
                    <DropdownMenuItem onSelect={() => onArchive(project)}>
                        {archived ? (
                            <>
                                <ArchiveRestore /> Restore
                            </>
                        ) : (
                            <>
                                <Archive /> Archive
                            </>
                        )}
                    </DropdownMenuItem>
                )}
                {canDelete && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => onDelete(project)}
                        >
                            <Trash2 /> Delete
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
