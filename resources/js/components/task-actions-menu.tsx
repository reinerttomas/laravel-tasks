import { router } from '@inertiajs/react';
import { Copy, MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
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
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { destroy } from '@/routes/tasks';
import type { TaskDetail } from '@/types';

type Props = {
    teamSlug: string;
    task: TaskDetail;
    canDelete: boolean;
};

export default function TaskActionsMenu({ teamSlug, task, canDelete }: Props) {
    const [deleting, setDeleting] = useState(false);

    const copyReference = async () => {
        await navigator.clipboard.writeText(task.reference);
        toast.success(`Copied ${task.reference}`);
    };

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label={`Actions for ${task.reference}`}
                        data-test="task-actions"
                    >
                        <MoreHorizontal />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem onSelect={copyReference}>
                        <Copy /> Copy reference
                    </DropdownMenuItem>
                    {canDelete && (
                        <>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => setDeleting(true)}
                                data-test="task-delete"
                            >
                                <Trash2 /> Delete task
                            </DropdownMenuItem>
                        </>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>

            <AlertDialog open={deleting} onOpenChange={setDeleting}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Delete {task.reference}?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            “{task.title}” and all its comments will be
                            permanently deleted. This cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            data-test="task-delete-confirm"
                            onClick={() =>
                                router.visit(
                                    destroy({
                                        current_team: teamSlug,
                                        task: task.id,
                                    }),
                                )
                            }
                        >
                            Delete task
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
