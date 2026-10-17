import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { TaskCounts, TaskStatus } from '@/types';

// Canceled tasks are left out: they are neither progress nor remaining work.
const segments: { status: TaskStatus; label: string; className: string }[] = [
    { status: 'done', label: 'Done', className: 'bg-indigo-500' },
    { status: 'in_progress', label: 'In Progress', className: 'bg-amber-500' },
    { status: 'todo', label: 'Todo', className: 'bg-foreground/40' },
    { status: 'backlog', label: 'Backlog', className: 'bg-foreground/15' },
];

export default function TaskStatusBar({ counts }: { counts: TaskCounts }) {
    const total = segments.reduce(
        (sum, segment) => sum + counts[segment.status],
        0,
    );

    return (
        <div
            className="flex h-2 overflow-hidden rounded-full bg-muted"
            data-test="task-status-bar"
        >
            {total > 0 &&
                segments.map(
                    (segment) =>
                        counts[segment.status] > 0 && (
                            <Tooltip key={segment.status}>
                                <TooltipTrigger asChild>
                                    <div
                                        className={segment.className}
                                        style={{
                                            width: `${(counts[segment.status] / total) * 100}%`,
                                        }}
                                    />
                                </TooltipTrigger>
                                <TooltipContent>
                                    {counts[segment.status]} {segment.label}
                                </TooltipContent>
                            </Tooltip>
                        ),
                )}
        </div>
    );
}
