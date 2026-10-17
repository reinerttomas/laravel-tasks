import { format, isBefore, parseISO, startOfToday } from 'date-fns';
import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    Circle,
    CircleCheck,
    CircleDashed,
    CircleDot,
    CircleX,
    Minus,
    SignalHigh,
    SignalLow,
    SignalMedium,
} from 'lucide-react';
import type { TaskPriority, TaskStatus } from '@/types';

type Option<T extends string> = {
    value: T;
    label: string;
    icon: LucideIcon;
    iconClassName: string;
};

// Workflow order, mirrors the TaskStatus PHP enum.
export const taskStatuses: Option<TaskStatus>[] = [
    {
        value: 'backlog',
        label: 'Backlog',
        icon: CircleDashed,
        iconClassName: 'text-muted-foreground',
    },
    {
        value: 'todo',
        label: 'Todo',
        icon: Circle,
        iconClassName: 'text-foreground/70',
    },
    {
        value: 'in_progress',
        label: 'In Progress',
        icon: CircleDot,
        iconClassName: 'text-amber-500',
    },
    {
        value: 'done',
        label: 'Done',
        icon: CircleCheck,
        iconClassName: 'text-indigo-500',
    },
    {
        value: 'canceled',
        label: 'Canceled',
        icon: CircleX,
        iconClassName: 'text-muted-foreground',
    },
];

// Most urgent first, mirrors the TaskPriority PHP enum.
export const taskPriorities: Option<TaskPriority>[] = [
    {
        value: 'urgent',
        label: 'Urgent',
        icon: AlertTriangle,
        iconClassName: 'text-orange-500',
    },
    {
        value: 'high',
        label: 'High',
        icon: SignalHigh,
        iconClassName: 'text-muted-foreground',
    },
    {
        value: 'medium',
        label: 'Medium',
        icon: SignalMedium,
        iconClassName: 'text-muted-foreground',
    },
    {
        value: 'low',
        label: 'Low',
        icon: SignalLow,
        iconClassName: 'text-muted-foreground',
    },
    {
        value: 'none',
        label: 'No priority',
        icon: Minus,
        iconClassName: 'text-muted-foreground',
    },
];

export const openTaskStatuses: TaskStatus[] = [
    'backlog',
    'todo',
    'in_progress',
];

export function taskStatus(value: TaskStatus): Option<TaskStatus> {
    return taskStatuses.find((status) => status.value === value)!;
}

export function taskPriority(value: TaskPriority): Option<TaskPriority> {
    return taskPriorities.find((priority) => priority.value === value)!;
}

/** Due dates are calendar days (Y-m-d); a task is overdue from the day after. */
export function isOverdue(dueDate: string | null): boolean {
    return dueDate !== null && isBefore(parseISO(dueDate), startOfToday());
}

export function formatDay(date: string | null): string {
    return date ? format(parseISO(date), 'd MMM') : '';
}
