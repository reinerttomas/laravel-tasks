import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy } from '@/routes/projects';
import type { Project } from '@/types';

type Props = {
    teamSlug: string;
    project: Project | null;
    onOpenChange: (open: boolean) => void;
};

export default function DeleteProjectDialog({
    teamSlug,
    project,
    onOpenChange,
}: Props) {
    const [typedIdentifier, setTypedIdentifier] = useState('');

    const handleOpenChange = (open: boolean) => {
        onOpenChange(open);

        if (!open) {
            setTypedIdentifier('');
        }
    };

    return (
        <AlertDialog open={project !== null} onOpenChange={handleOpenChange}>
            <AlertDialogContent>
                {project && (
                    <Form
                        {...destroy.form({
                            current_team: teamSlug,
                            project: project.id,
                        })}
                        className="space-y-4"
                        onSuccess={() => handleOpenChange(false)}
                    >
                        {({ errors, processing }) => (
                            <>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        Delete {project.name}?
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        All its tasks and comments will be
                                        permanently deleted. This cannot be
                                        undone.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>

                                <div className="grid gap-2">
                                    <Label htmlFor="delete-project-identifier">
                                        Type{' '}
                                        <span className="font-mono font-semibold">
                                            {project.identifier}
                                        </span>{' '}
                                        to confirm
                                    </Label>
                                    <Input
                                        id="delete-project-identifier"
                                        name="identifier"
                                        data-test="delete-project-identifier"
                                        value={typedIdentifier}
                                        onChange={(event) =>
                                            setTypedIdentifier(
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                        className="font-mono"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.identifier} />
                                </div>

                                <AlertDialogFooter>
                                    <AlertDialogCancel type="button">
                                        Cancel
                                    </AlertDialogCancel>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        data-test="delete-project-confirm"
                                        disabled={
                                            typedIdentifier !==
                                                project.identifier || processing
                                        }
                                    >
                                        Delete project
                                    </Button>
                                </AlertDialogFooter>
                            </>
                        )}
                    </Form>
                )}
            </AlertDialogContent>
        </AlertDialog>
    );
}
