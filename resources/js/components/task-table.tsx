import { Link, usePage } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { ReactNode } from 'react';
import TaskPriorityIcon from '@/components/task-priority-icon';
import TaskStatusIcon from '@/components/task-status-icon';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { TaskTableState } from '@/hooks/use-task-table';
import { useInitials } from '@/hooks/use-initials';
import { projectColorClass } from '@/lib/project-colors';
import { formatDay, isOverdue, taskPriority, taskStatus } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { show } from '@/routes/tasks';
import type { TaskRow, TaskSortColumn } from '@/types';

type Props = {
    tasks: TaskRow[];
    table: TaskTableState;
    /** The project task list shows who works on a task; My tasks shows the project instead. */
    context: 'assignee' | 'project';
};

export default function TaskTable({ tasks, table, context }: Props) {
    const teamSlug = usePage().props.currentTeam?.slug ?? '';
    const header = (
        column: TaskSortColumn,
        label: string,
        className?: string,
    ) => (
        <TableHead className={className}>
            <button
                type="button"
                className="inline-flex items-center gap-1 hover:text-foreground"
                onClick={() => table.toggleSort(column)}
                data-test={`sort-${column}`}
            >
                {label}
                {table.sortColumn !== column ? (
                    <ArrowUpDown className="size-3.5 opacity-50" />
                ) : table.sortDescending ? (
                    <ArrowDown className="size-3.5" />
                ) : (
                    <ArrowUp className="size-3.5" />
                )}
            </button>
        </TableHead>
    );

    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader className="bg-muted/50">
                    <TableRow>
                        {header('number', 'ID', 'w-24')}
                        {header('title', 'Title')}
                        {header('status', 'Status', 'w-36')}
                        {header('priority', 'Priority', 'w-32')}
                        <TableHead className="w-48">
                            {context === 'assignee' ? 'Assignee' : 'Project'}
                        </TableHead>
                        {header('due_date', 'Due', 'w-24')}
                        {header('updated_at', 'Updated', 'w-24')}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {tasks.map((task) => (
                        <TableRow key={task.id} data-test="task-row">
                            <TableCell className="font-mono text-xs text-muted-foreground">
                                {task.reference}
                            </TableCell>
                            <TableCell className="max-w-0 truncate font-medium">
                                <Link
                                    href={show({
                                        current_team: teamSlug,
                                        task: task.id,
                                    })}
                                    className="hover:underline"
                                    data-test="task-link"
                                >
                                    {task.title}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline" className="gap-1.5">
                                    <TaskStatusIcon
                                        status={task.status}
                                        className="size-3.5"
                                    />
                                    {taskStatus(task.status).label}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <span className="inline-flex items-center gap-1.5">
                                    <TaskPriorityIcon
                                        priority={task.priority}
                                    />
                                    {taskPriority(task.priority).label}
                                </span>
                            </TableCell>
                            <TableCell>
                                {context === 'assignee' ? (
                                    <Assignee assignee={task.assignee} />
                                ) : (
                                    <span className="inline-flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'size-2.5 shrink-0 rounded-sm',
                                                projectColorClass(
                                                    task.project.color,
                                                ),
                                            )}
                                        />
                                        <span className="font-mono text-xs">
                                            {task.project.identifier}
                                        </span>
                                    </span>
                                )}
                            </TableCell>
                            <TableCell
                                className={cn(
                                    'text-muted-foreground',
                                    isOverdue(task.dueDate) &&
                                        'text-red-600 dark:text-red-400',
                                )}
                            >
                                {formatDay(task.dueDate) || '—'}
                            </TableCell>
                            <TableCell className="text-muted-foreground">
                                {formatDay(task.updatedAt)}
                            </TableCell>
                        </TableRow>
                    ))}
                    {tasks.length === 0 && (
                        <EmptyRow>
                            {table.isFiltered
                                ? 'No tasks match these filters.'
                                : 'No tasks yet.'}
                        </EmptyRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );
}

function EmptyRow({ children }: { children: ReactNode }) {
    return (
        <TableRow className="hover:bg-transparent">
            <TableCell
                colSpan={7}
                className="py-12 text-center text-muted-foreground"
            >
                {children}
            </TableCell>
        </TableRow>
    );
}

function Assignee({ assignee }: { assignee: TaskRow['assignee'] }) {
    const getInitials = useInitials();

    if (!assignee) {
        return (
            <span className="inline-flex items-center gap-2 text-muted-foreground">
                <span className="inline-block size-6 shrink-0 rounded-full border border-dashed border-muted-foreground/50" />
                Unassigned
            </span>
        );
    }

    return (
        <span className="inline-flex min-w-0 items-center gap-2">
            <Avatar className="size-6">
                <AvatarFallback className="text-[10px]">
                    {getInitials(assignee.name)}
                </AvatarFallback>
            </Avatar>
            <span className="truncate">{assignee.name}</span>
        </span>
    );
}
