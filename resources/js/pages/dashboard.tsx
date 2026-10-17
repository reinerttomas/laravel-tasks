import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { AlertCircle, CircleDot, ListTodo } from 'lucide-react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import TaskPriorityIcon from '@/components/task-priority-icon';
import TaskStatusIcon from '@/components/task-status-icon';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { projectColorClass } from '@/lib/project-colors';
import { formatDay, isOverdue, taskStatus } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/tasks';
import type {
    DashboardInvitation,
    TaskCounts,
    TaskRow,
    TaskStatus,
} from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    teamTaskCounts: TaskCounts;
    myTasks: {
        overdue: TaskRow[];
        inProgress: TaskRow[];
        upNext: TaskRow[];
    };
};

const stripStatuses: TaskStatus[] = ['backlog', 'todo', 'in_progress', 'done'];

function greeting(): string {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
}

export default function Dashboard({
    pendingInvitations = [],
    teamTaskCounts,
    myTasks,
}: Props) {
    const { auth } = usePage().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const firstName = auth.user.name.split(' ')[0];

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="mx-auto w-full max-w-3xl px-4 py-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {greeting()}, {firstName}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    You have {myTasks.inProgress.length} tasks in progress and{' '}
                    {myTasks.overdue.length} overdue.
                </p>

                <div
                    className="mt-6 flex flex-wrap gap-x-6 gap-y-2 rounded-lg bg-muted/50 px-4 py-3 text-sm"
                    data-test="team-task-counts"
                >
                    <span className="text-muted-foreground">Team</span>
                    {stripStatuses.map((status) => (
                        <span
                            key={status}
                            className="flex items-center gap-1.5"
                        >
                            <TaskStatusIcon
                                status={status}
                                className="size-3.5"
                            />
                            <span className="font-medium tabular-nums">
                                {teamTaskCounts[status]}
                            </span>
                            <span className="text-muted-foreground">
                                {taskStatus(status).label}
                            </span>
                        </span>
                    ))}
                </div>

                <Separator className="my-8" />

                <div className="space-y-8">
                    <TaskSection
                        icon={AlertCircle}
                        title="Overdue"
                        tasks={myTasks.overdue}
                        empty="Nothing overdue."
                        className="text-red-600 dark:text-red-400"
                    />
                    <TaskSection
                        icon={CircleDot}
                        title="In progress"
                        tasks={myTasks.inProgress}
                        empty="Nothing in progress."
                    />
                    <TaskSection
                        icon={ListTodo}
                        title="Up next"
                        tasks={myTasks.upNext}
                        empty="No tasks waiting for you."
                    />
                </div>
            </div>
        </>
    );
}

function TaskSection({
    icon: Icon,
    title,
    tasks,
    empty,
    className,
}: {
    icon: LucideIcon;
    title: string;
    tasks: TaskRow[];
    empty: string;
    className?: string;
}) {
    return (
        <section data-test={`section-${title.toLowerCase().replace(' ', '-')}`}>
            <h2
                className={cn(
                    'mb-2 flex items-center gap-2 text-sm font-medium',
                    className,
                )}
            >
                <Icon className="size-4" />
                {title}
                <span className="text-muted-foreground tabular-nums">
                    {tasks.length}
                </span>
            </h2>
            {tasks.length === 0 ? (
                <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                    {empty}
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {tasks.map((task) => (
                        <TaskLine key={task.id} task={task} />
                    ))}
                </ul>
            )}
        </section>
    );
}

function TaskLine({ task }: { task: TaskRow }) {
    const { currentTeam, today } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const overdue = isOverdue(task.dueDate, today);

    return (
        <li className="flex items-center gap-3 px-4 py-3 text-sm">
            <TaskStatusIcon status={task.status} />
            <div className="min-w-0 flex-1">
                <Link
                    href={show({ current_team: teamSlug, task: task.id })}
                    className="block truncate font-medium hover:underline"
                    data-test="task-link"
                >
                    {task.title}
                </Link>
                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span
                        className={cn(
                            'size-2 shrink-0 rounded-full',
                            projectColorClass(task.project.color),
                        )}
                    />
                    <span className="font-mono">{task.reference}</span>
                    <span>· {taskStatus(task.status).label}</span>
                </p>
            </div>
            <TaskPriorityIcon priority={task.priority} />
            {task.dueDate && (
                <Badge
                    variant="outline"
                    className={cn(
                        'tabular-nums',
                        overdue &&
                            'border-red-500/40 text-red-600 dark:text-red-400',
                    )}
                >
                    {formatDay(task.dueDate)}
                </Badge>
            )}
        </li>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
