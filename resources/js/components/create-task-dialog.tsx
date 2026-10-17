import { Form } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TaskPriorityIcon from '@/components/task-priority-icon';
import TaskStatusIcon from '@/components/task-status-icon';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { formatDate } from '@/lib/dates';
import { taskPriorities, taskStatuses } from '@/lib/tasks';
import { store } from '@/routes/tasks';
import type { Project, AssignableMember } from '@/types';

type Props = {
    teamSlug: string;
    project: Project;
    members: AssignableMember[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

// Radix Select cannot hold an empty value, so "nobody" gets its own item.
const UNASSIGNED = 'unassigned';

export default function CreateTaskDialog({
    teamSlug,
    project,
    members,
    open,
    onOpenChange,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                {open && (
                    <CreateTaskForm
                        teamSlug={teamSlug}
                        project={project}
                        members={members}
                        onCreated={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function CreateTaskForm({
    teamSlug,
    project,
    members,
    onCreated,
}: {
    teamSlug: string;
    project: Project;
    members: AssignableMember[];
    onCreated: () => void;
}) {
    const [assignee, setAssignee] = useState(UNASSIGNED);
    const [dueDate, setDueDate] = useState<Date | undefined>();

    return (
        <Form
            {...store.form({ current_team: teamSlug, project: project.id })}
            className="space-y-5"
            options={{ preserveScroll: true }}
            onSuccess={onCreated}
        >
            {({ errors, processing }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>New task</DialogTitle>
                        <DialogDescription>
                            Add a task to {project.name}. It gets the next{' '}
                            <span className="font-mono">
                                {project.identifier}
                            </span>{' '}
                            number.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="task-title">Title</Label>
                        <Input
                            id="task-title"
                            name="title"
                            required
                            autoFocus
                            maxLength={255}
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="task-description">Description</Label>
                        <Textarea
                            id="task-description"
                            name="description"
                            rows={4}
                            placeholder="Optional, markdown supported"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="task-status">Status</Label>
                            <Select name="status" defaultValue="backlog">
                                <SelectTrigger
                                    id="task-status"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {taskStatuses.map((status) => (
                                        <SelectItem
                                            key={status.value}
                                            value={status.value}
                                        >
                                            <TaskStatusIcon
                                                status={status.value}
                                            />
                                            {status.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task-priority">Priority</Label>
                            <Select name="priority" defaultValue="none">
                                <SelectTrigger
                                    id="task-priority"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {taskPriorities.map((priority) => (
                                        <SelectItem
                                            key={priority.value}
                                            value={priority.value}
                                        >
                                            <TaskPriorityIcon
                                                priority={priority.value}
                                            />
                                            {priority.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.priority} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task-assignee">Assignee</Label>
                            <Select
                                value={assignee}
                                onValueChange={setAssignee}
                            >
                                <SelectTrigger
                                    id="task-assignee"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={UNASSIGNED}>
                                        Unassigned
                                    </SelectItem>
                                    {members.map((member) => (
                                        <SelectItem
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <input
                                type="hidden"
                                name="assignee_id"
                                value={assignee === UNASSIGNED ? '' : assignee}
                            />
                            <InputError message={errors.assignee_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task-due-date">Due date</Label>
                            <div className="flex gap-1">
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button
                                            id="task-due-date"
                                            type="button"
                                            variant="outline"
                                            className="flex-1 justify-start font-normal"
                                        >
                                            <CalendarIcon />
                                            {dueDate ? (
                                                formatDate(dueDate)
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    No due date
                                                </span>
                                            )}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        className="w-auto p-0"
                                        align="start"
                                    >
                                        <Calendar
                                            mode="single"
                                            selected={dueDate}
                                            onSelect={setDueDate}
                                        />
                                    </PopoverContent>
                                </Popover>
                                {dueDate && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Clear due date"
                                        onClick={() => setDueDate(undefined)}
                                    >
                                        <X />
                                    </Button>
                                )}
                            </div>
                            <input
                                type="hidden"
                                name="due_date"
                                value={
                                    dueDate ? format(dueDate, 'yyyy-MM-dd') : ''
                                }
                            />
                            <InputError message={errors.due_date} />
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="ghost">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="task-submit"
                        >
                            Create task
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
