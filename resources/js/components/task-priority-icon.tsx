import { taskPriority } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import type { TaskPriority } from '@/types';

export default function TaskPriorityIcon({
    priority,
    className,
}: {
    priority: TaskPriority;
    className?: string;
}) {
    const { icon: Icon, iconClassName } = taskPriority(priority);

    return <Icon className={cn('size-4 shrink-0', iconClassName, className)} />;
}
