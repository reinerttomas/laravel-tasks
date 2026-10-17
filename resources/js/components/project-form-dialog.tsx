import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import ProjectIdentifier from '@/components/project-identifier';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { projectColors } from '@/lib/project-colors';
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/projects';
import type { Project, ProjectColor } from '@/types';

type Props = {
    teamSlug: string;
    project: Project | null;
    suggestedColor: ProjectColor;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const identifierPattern = /^[A-Z]{2,5}$/;

function suggestIdentifier(name: string): string {
    const words = name
        .normalize('NFD')
        .toUpperCase()
        .replace(/[^A-Z\s]/g, '')
        .split(/\s+/)
        .filter(Boolean);

    if (words.length === 1) {
        return words[0].slice(0, 3);
    }

    return words
        .map((word) => word[0])
        .join('')
        .slice(0, 5);
}

export default function ProjectFormDialog({
    teamSlug,
    project,
    suggestedColor,
    open,
    onOpenChange,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                {open && (
                    <ProjectForm
                        teamSlug={teamSlug}
                        project={project}
                        suggestedColor={suggestedColor}
                        onSaved={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function ProjectForm({
    teamSlug,
    project,
    suggestedColor,
    onSaved,
}: {
    teamSlug: string;
    project: Project | null;
    suggestedColor: ProjectColor;
    onSaved: () => void;
}) {
    const [identifier, setIdentifier] = useState(project?.identifier ?? '');
    const [identifierEdited, setIdentifierEdited] = useState(project !== null);
    const [color, setColor] = useState<ProjectColor>(
        project?.color ?? suggestedColor,
    );

    const identifierValid = identifierPattern.test(identifier);
    const renamesIdentifier =
        project !== null &&
        identifierValid &&
        project.identifier !== identifier;

    return (
        <Form
            {...(project
                ? update.form({ current_team: teamSlug, project: project.id })
                : store.form({ current_team: teamSlug }))}
            className="space-y-6"
            onSuccess={onSaved}
        >
            {({ errors, processing }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>
                            {project ? `Edit ${project.name}` : 'New project'}
                        </DialogTitle>
                        <DialogDescription>
                            Every member of the team can see the project and its
                            tasks.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="project-name">Name</Label>
                        <Input
                            id="project-name"
                            name="name"
                            defaultValue={project?.name}
                            data-test="project-name"
                            autoFocus
                            required
                            onChange={(event) => {
                                if (!identifierEdited) {
                                    setIdentifier(
                                        suggestIdentifier(event.target.value),
                                    );
                                }
                            }}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Color</Label>
                        <input type="hidden" name="color" value={color} />
                        <div className="flex items-center gap-3">
                            <ProjectIdentifier
                                identifier={identifier}
                                color={color}
                            />
                            <ToggleGroup
                                type="single"
                                value={color}
                                onValueChange={(value) =>
                                    value && setColor(value as ProjectColor)
                                }
                                className="flex-wrap gap-1.5"
                            >
                                {projectColors.map((option) => (
                                    <ToggleGroupItem
                                        key={option.value}
                                        value={option.value}
                                        aria-label={option.label}
                                        title={option.label}
                                        className="size-7 min-w-0 rounded-full p-0 data-[state=on]:bg-transparent data-[state=on]:ring-2 data-[state=on]:ring-ring data-[state=on]:ring-offset-2 data-[state=on]:ring-offset-background"
                                    >
                                        <span
                                            className={cn(
                                                'size-5 rounded-full',
                                                option.className,
                                            )}
                                        />
                                    </ToggleGroupItem>
                                ))}
                            </ToggleGroup>
                        </div>
                        <InputError message={errors.color} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="project-identifier">Identifier</Label>
                        <Input
                            id="project-identifier"
                            name="identifier"
                            value={identifier}
                            data-test="project-identifier"
                            maxLength={5}
                            className="w-32 font-mono uppercase"
                            aria-invalid={identifier !== '' && !identifierValid}
                            onChange={(event) => {
                                setIdentifierEdited(true);
                                setIdentifier(
                                    event.target.value
                                        .toUpperCase()
                                        .replace(/[^A-Z]/g, ''),
                                );
                            }}
                        />
                        <p className="text-xs text-muted-foreground">
                            2–5 uppercase letters, unique in the team. Task
                            references will look like{' '}
                            <span className="font-mono">
                                {identifier || 'WEB'}-42
                            </span>
                            .
                        </p>
                        {renamesIdentifier && (
                            <p className="text-xs text-amber-600 dark:text-amber-500">
                                Existing references change from{' '}
                                <span className="font-mono">
                                    {project.identifier}-…
                                </span>{' '}
                                to{' '}
                                <span className="font-mono">
                                    {identifier}-…
                                </span>
                                ; old links stop working.
                            </p>
                        )}
                        <InputError message={errors.identifier} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="project-description">Description</Label>
                        <Textarea
                            id="project-description"
                            name="description"
                            defaultValue={project?.description ?? ''}
                            rows={3}
                            placeholder="Optional"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            data-test="project-submit"
                            disabled={!identifierValid || processing}
                        >
                            {project ? 'Save' : 'Create project'}
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
