import { router } from '@inertiajs/react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { archive, restore } from '@/routes/projects';
import type { Project } from '@/types';

type Props = {
    teamSlug: string;
    project: Project | null;
    onOpenChange: (open: boolean) => void;
};

export default function ArchiveProjectDialog({
    teamSlug,
    project,
    onOpenChange,
}: Props) {
    const restoring = project?.archivedAt != null;

    const confirm = () => {
        if (!project) {
            return;
        }

        const route = restoring ? restore : archive;

        router.visit(route({ current_team: teamSlug, project: project.id }), {
            preserveScroll: true,
        });
    };

    return (
        <AlertDialog open={project !== null} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {restoring ? 'Restore' : 'Archive'} {project?.name}?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {restoring
                            ? 'The project returns to the active list and its tasks can be edited again.'
                            : 'The project is hidden from the active list and its tasks and comments become read-only. You can restore it later.'}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        data-test="archive-project-confirm"
                        onClick={confirm}
                    >
                        {restoring ? 'Restore' : 'Archive'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
