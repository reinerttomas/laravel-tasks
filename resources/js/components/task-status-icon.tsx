import { taskStatus } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import type { TaskStatus } from '@/types';

export default function TaskStatusIcon({
    status,
    className,
}: {
    status: TaskStatus;
    className?: string;
}) {
    const { icon: Icon, iconClassName } = taskStatus(status);

    return <Icon className={cn('size-4 shrink-0', iconClassName, className)} />;
}
