import { router, usePage } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { CalendarDays, Check, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Markdown from '@/components/markdown';
import TaskPriorityIcon from '@/components/task-priority-icon';
import TaskStatusIcon from '@/components/task-status-icon';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/hooks/use-initials';
import { isOverdue, taskPriorities, taskStatuses } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { update as updateRoute } from '@/routes/tasks';
import type {
    AssignableMember,
    TaskDetail,
    TaskPriority,
    TaskStatus,
} from '@/types';

type SaveTask = (
    data: Record<string, string | null>,
    optimistic: Partial<TaskDetail>,
) => void;

type EditorProps = {
    task: TaskDetail;
    save: SaveTask;
    disabled: boolean;
};

const chip =
    'h-7 w-auto gap-1.5 rounded-md px-2 text-xs font-medium shadow-none disabled:cursor-default disabled:opacity-100';

/** Saves one or more task fields immediately, showing the change before the server confirms it. */
export function useSaveTask(teamSlug: string, task: TaskDetail): SaveTask {
    return (data, optimistic) => {
        router
            .optimistic<{ task: TaskDetail }>((props) => ({
                task: { ...props.task, ...optimistic },
            }))
            .patch(
                updateRoute.url({ current_team: teamSlug, task: task.id }),
                data,
                {
                    preserveScroll: true,
                    // Concurrent inline edits must not cancel each other.
                    async: true,
                    onError: (errors) => {
                        toast.error(
                            Object.values(errors)[0] ??
                                'The task could not be saved.',
                        );
                    },
                },
            );
    };
}

export function TitleEditor({ task, save, disabled }: EditorProps) {
    return (
        <Textarea
            // Remount when the saved title changes so the field shows it.
            key={task.title}
            defaultValue={task.title}
            disabled={disabled}
            rows={1}
            maxLength={255}
            aria-label="Title"
            data-test="task-title"
            onKeyDown={(event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    event.currentTarget.blur();
                }

                if (event.key === 'Escape') {
                    event.currentTarget.value = task.title;
                    event.currentTarget.blur();
                }
            }}
            onBlur={(event) => {
                const title = event.target.value.trim();

                if (title === '') {
                    event.target.value = task.title;
                } else if (title !== task.title) {
                    save({ title }, { title });
                }
            }}
            className="-mx-2 field-sizing-content min-h-0 resize-none rounded-md border-0 px-2 py-1 text-2xl font-semibold tracking-tight shadow-none hover:bg-accent/50 focus-visible:bg-transparent focus-visible:ring-1 disabled:cursor-default disabled:opacity-100 disabled:hover:bg-transparent md:text-2xl dark:bg-transparent"
        />
    );
}

export function StatusPicker({ task, save, disabled }: EditorProps) {
    return (
        <Select
            value={task.status}
            disabled={disabled}
            onValueChange={(value: TaskStatus) =>
                save({ status: value }, { status: value })
            }
        >
            <SelectTrigger
                size="sm"
                className={chip}
                aria-label="Status"
                data-test="task-status"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {taskStatuses.map((status) => (
                    <SelectItem key={status.value} value={status.value}>
                        <TaskStatusIcon status={status.value} />
                        {status.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function PriorityPicker({ task, save, disabled }: EditorProps) {
    return (
        <Select
            value={task.priority}
            disabled={disabled}
            onValueChange={(value: TaskPriority) =>
                save({ priority: value }, { priority: value })
            }
        >
            <SelectTrigger
                size="sm"
                className={chip}
                aria-label="Priority"
                data-test="task-priority"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {taskPriorities.map((priority) => (
                    <SelectItem key={priority.value} value={priority.value}>
                        <TaskPriorityIcon priority={priority.value} />
                        {priority.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function MemberAvatar({
    member,
    className,
}: {
    member: AssignableMember | null;
    className?: string;
}) {
    const getInitials = useInitials();

    if (!member) {
        return (
            <span
                className={cn(
                    'inline-block shrink-0 rounded-full border border-dashed border-muted-foreground/50',
                    className,
                )}
            />
        );
    }

    return (
        <Avatar className={className}>
            <AvatarFallback className="text-[9px]">
                {getInitials(member.name)}
            </AvatarFallback>
        </Avatar>
    );
}

export function AssigneePicker({
    task,
    save,
    disabled,
    members,
}: EditorProps & { members: AssignableMember[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild disabled={disabled}>
                <Button
                    variant="outline"
                    size="sm"
                    className={cn(
                        chip,
                        !task.assignee && 'text-muted-foreground',
                    )}
                    data-test="task-assignee"
                >
                    <MemberAvatar member={task.assignee} className="size-4" />
                    {task.assignee?.name ?? 'Unassigned'}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-60 p-0" align="start">
                <Command>
                    <CommandInput placeholder="Assign to…" />
                    <CommandList>
                        <CommandEmpty>No team member found.</CommandEmpty>
                        <CommandGroup>
                            {[null, ...members].map((member) => (
                                <CommandItem
                                    key={member?.id ?? 'unassigned'}
                                    // cmdk filters on the value, so use the name, keyed by id for duplicate names.
                                    value={
                                        member
                                            ? `${member.name} ${member.id}`
                                            : 'Unassigned'
                                    }
                                    onSelect={() => {
                                        save(
                                            { assignee_id: member?.id ?? null },
                                            { assignee: member },
                                        );
                                        setOpen(false);
                                    }}
                                >
                                    <MemberAvatar
                                        member={member}
                                        className="size-5"
                                    />
                                    {member?.name ?? 'Unassigned'}
                                    {(task.assignee?.id ?? null) ===
                                        (member?.id ?? null) && (
                                        <Check className="ml-auto" />
                                    )}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

export function DueDatePicker({ task, save, disabled }: EditorProps) {
    const { today } = usePage().props;
    const [open, setOpen] = useState(false);
    const dueDate = task.dueDate ? parseISO(task.dueDate) : undefined;

    const saveDueDate = (date: string | null) => {
        save({ due_date: date }, { dueDate: date });
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild disabled={disabled}>
                <Button
                    variant="outline"
                    size="sm"
                    className={cn(
                        chip,
                        !dueDate && 'text-muted-foreground',
                        isOverdue(task.dueDate, today) &&
                            'text-red-600 dark:text-red-400',
                    )}
                    data-test="task-due-date"
                >
                    <CalendarDays />
                    {dueDate ? format(dueDate, 'd MMM yyyy') : 'Due date'}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={dueDate}
                    defaultMonth={dueDate}
                    onSelect={(date) =>
                        saveDueDate(date ? format(date, 'yyyy-MM-dd') : null)
                    }
                />
                {dueDate && (
                    <div className="border-t p-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full"
                            onClick={() => saveDueDate(null)}
                        >
                            <X /> Clear due date
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}

export function DescriptionEditor({ task, save, disabled }: EditorProps) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState('');

    const submit = () => {
        const description = draft.trim() === '' ? null : draft;

        if (description !== task.description) {
            save({ description }, { description });
        }

        setEditing(false);
    };

    if (editing) {
        return (
            <div className="space-y-2">
                <Textarea
                    autoFocus
                    rows={10}
                    value={draft}
                    maxLength={20000}
                    aria-label="Description"
                    data-test="task-description-input"
                    onChange={(event) => setDraft(event.target.value)}
                    onKeyDown={(event) => {
                        if (
                            event.key === 'Enter' &&
                            (event.metaKey || event.ctrlKey)
                        ) {
                            event.preventDefault();
                            submit();
                        }

                        if (event.key === 'Escape') {
                            setEditing(false);
                        }
                    }}
                />
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="text-xs text-muted-foreground">
                        Markdown supported · ⌘↵ to save · Esc to cancel
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditing(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            size="sm"
                            onClick={submit}
                            data-test="task-description-save"
                        >
                            Save
                        </Button>
                    </div>
                </div>
            </div>
        );
    }

    const content = task.description ? (
        <Markdown>{task.description}</Markdown>
    ) : (
        <p className="text-sm text-muted-foreground">
            {disabled ? 'No description.' : 'Add a description…'}
        </p>
    );

    if (disabled) {
        return <div className="py-2">{content}</div>;
    }

    return (
        <div
            role="button"
            tabIndex={0}
            data-test="task-description"
            onClick={(event) => {
                // Let links in the rendered markdown open instead of starting an edit.
                if ((event.target as HTMLElement).closest('a')) {
                    return;
                }

                setDraft(task.description ?? '');
                setEditing(true);
            }}
            onKeyDown={(event) => {
                if (
                    event.key === 'Enter' &&
                    event.target === event.currentTarget
                ) {
                    setDraft(task.description ?? '');
                    setEditing(true);
                }
            }}
            className="-mx-2 cursor-text rounded-md p-2 hover:bg-accent/50 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
        >
            {content}
        </div>
    );
}
