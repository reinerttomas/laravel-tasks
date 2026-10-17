import { Head, usePage } from '@inertiajs/react';
import CommentThread from '@/components/comment-thread';
import RelativeTime from '@/components/relative-time';
import TaskActionsMenu from '@/components/task-actions-menu';
import {
    AssigneePicker,
    DescriptionEditor,
    DueDatePicker,
    PriorityPicker,
    StatusPicker,
    TitleEditor,
    useSaveTask,
} from '@/components/task-property-editors';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { index, show as showProject } from '@/routes/projects';
import { show } from '@/routes/tasks';
import type { AssignableMember, TaskComment, TaskDetail } from '@/types';

type Props = {
    task: TaskDetail;
    comments: TaskComment[];
    members: AssignableMember[];
    can: { update: boolean; delete: boolean; createComment: boolean };
};

export default function TasksShow({ task, comments, members, can }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';
    const save = useSaveTask(teamSlug, task);
    const editor = { task, save, disabled: !can.update };

    return (
        <>
            <Head title={`${task.reference} ${task.title}`} />

            <div className="mx-auto w-full max-w-3xl px-4 py-6 pb-24 md:py-8">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <span className="font-mono">{task.reference}</span>
                    <span>·</span>
                    <span className="min-w-0 truncate">
                        opened by{' '}
                        <span data-test="task-author">
                            {task.author?.name ?? 'Deleted user'}
                        </span>
                        {task.updatedAt && (
                            <>
                                , updated <RelativeTime date={task.updatedAt} />
                            </>
                        )}
                    </span>
                    {task.project.archivedAt !== null && (
                        <Badge variant="secondary">Archived</Badge>
                    )}
                    <div className="ml-auto">
                        <TaskActionsMenu
                            teamSlug={teamSlug}
                            task={task}
                            canDelete={can.delete}
                        />
                    </div>
                </div>

                <div className="mt-2">
                    <TitleEditor {...editor} />
                </div>

                <div className="mt-3 flex flex-wrap items-center gap-2">
                    <StatusPicker {...editor} />
                    <PriorityPicker {...editor} />
                    <AssigneePicker {...editor} members={members} />
                    <DueDatePicker {...editor} />
                </div>

                <div className="mt-6">
                    <DescriptionEditor {...editor} />
                </div>

                <Separator className="my-8" />

                <h2 className="mb-4 font-medium">Comments</h2>
                <CommentThread
                    teamSlug={teamSlug}
                    task={task}
                    comments={comments}
                    canComment={can.createComment}
                />
            </div>
        </>
    );
}

TasksShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    task: TaskDetail;
}) => {
    const teamSlug = props.currentTeam?.slug ?? '';

    return {
        breadcrumbs: [
            { title: 'Projects', href: index(teamSlug) },
            {
                title: props.task.project.name,
                href: showProject({
                    current_team: teamSlug,
                    project: props.task.project.id,
                }),
            },
            {
                title: props.task.reference,
                href: show({ current_team: teamSlug, task: props.task.id }),
            },
        ],
    };
};
