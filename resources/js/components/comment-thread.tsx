import { Form, router } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import Markdown from '@/components/markdown';
import RelativeTime from '@/components/relative-time';
import { MemberAvatar } from '@/components/task-property-editors';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Textarea } from '@/components/ui/textarea';
import { destroy, store, update } from '@/routes/comments';
import type { TaskComment, TaskDetail } from '@/types';

type Props = {
    teamSlug: string;
    task: TaskDetail;
    comments: TaskComment[];
    canComment: boolean;
};

function submitOnModEnter(event: KeyboardEvent<HTMLTextAreaElement>) {
    if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        event.currentTarget.form?.requestSubmit();
    }
}

export default function CommentThread({
    teamSlug,
    task,
    comments,
    canComment,
}: Props) {
    const [editingId, setEditingId] = useState<string | null>(null);
    const [deleting, setDeleting] = useState<TaskComment | null>(null);
    const editInput = useRef<HTMLTextAreaElement>(null);

    return (
        <div className="space-y-6">
            {comments.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    No comments yet.
                </p>
            )}

            {comments.map((comment) => (
                <div
                    key={comment.id}
                    className="flex gap-3"
                    data-test="comment"
                >
                    <MemberAvatar
                        member={comment.author}
                        className="mt-0.5 size-7"
                    />
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 text-sm">
                            <span className="font-medium">
                                {comment.author?.name ?? 'Deleted user'}
                            </span>
                            {comment.createdAt && (
                                <span className="text-muted-foreground">
                                    <RelativeTime date={comment.createdAt} />
                                    {comment.updatedAt !== comment.createdAt &&
                                        ' · edited'}
                                </span>
                            )}
                            {(comment.can.update || comment.can.delete) && (
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="ml-auto size-6"
                                            aria-label="Comment actions"
                                            data-test="comment-actions"
                                        >
                                            <MoreHorizontal />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        onCloseAutoFocus={(event) => {
                                            // The menu traps focus until it has closed, so the edit textarea can only take it now.
                                            if (
                                                editingId === comment.id &&
                                                editInput.current
                                            ) {
                                                event.preventDefault();
                                                editInput.current.focus();
                                            }
                                        }}
                                    >
                                        {comment.can.update && (
                                            <DropdownMenuItem
                                                onSelect={() =>
                                                    setEditingId(comment.id)
                                                }
                                                data-test="comment-edit"
                                            >
                                                <Pencil /> Edit
                                            </DropdownMenuItem>
                                        )}
                                        {comment.can.delete && (
                                            <DropdownMenuItem
                                                variant="destructive"
                                                onSelect={() =>
                                                    setDeleting(comment)
                                                }
                                                data-test="comment-delete"
                                            >
                                                <Trash2 /> Delete
                                            </DropdownMenuItem>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            )}
                        </div>

                        {editingId === comment.id ? (
                            <Form
                                {...update.form({
                                    current_team: teamSlug,
                                    task: task.id,
                                    comment: comment.id,
                                })}
                                options={{ preserveScroll: true }}
                                onSuccess={() => setEditingId(null)}
                                className="mt-2 space-y-2"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <Textarea
                                            ref={editInput}
                                            name="body"
                                            required
                                            rows={4}
                                            maxLength={20000}
                                            defaultValue={comment.body}
                                            aria-label="Comment"
                                            data-test="comment-edit-input"
                                            onKeyDown={(event) => {
                                                submitOnModEnter(event);

                                                if (event.key === 'Escape') {
                                                    setEditingId(null);
                                                }
                                            }}
                                        />
                                        <InputError message={errors.body} />
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setEditingId(null)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                size="sm"
                                                disabled={processing}
                                                data-test="comment-edit-save"
                                            >
                                                Save
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <div className="mt-1" data-test="comment-body">
                                <Markdown>{comment.body}</Markdown>
                            </div>
                        )}
                    </div>
                </div>
            ))}

            {canComment ? (
                <Form
                    {...store.form({ current_team: teamSlug, task: task.id })}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="space-y-2"
                >
                    {({ errors, processing }) => (
                        <>
                            <Textarea
                                name="body"
                                required
                                rows={3}
                                maxLength={20000}
                                placeholder="Leave a comment…"
                                aria-label="New comment"
                                data-test="comment-input"
                                onKeyDown={submitOnModEnter}
                            />
                            <InputError message={errors.body} />
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <span className="text-xs text-muted-foreground">
                                    Markdown supported · ⌘↵ to send
                                </span>
                                <Button
                                    size="sm"
                                    disabled={processing}
                                    data-test="comment-submit"
                                >
                                    Comment
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            ) : (
                <p className="text-sm text-muted-foreground">
                    {task.project.archivedAt !== null
                        ? 'This project is archived, so its comments are read-only.'
                        : 'You can read comments but not write them.'}
                </p>
            )}

            <AlertDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete comment?</AlertDialogTitle>
                        <AlertDialogDescription>
                            The comment will be permanently deleted. This cannot
                            be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            data-test="comment-delete-confirm"
                            onClick={() => {
                                if (deleting) {
                                    router.delete(
                                        destroy.url({
                                            current_team: teamSlug,
                                            task: task.id,
                                            comment: deleting.id,
                                        }),
                                        { preserveScroll: true },
                                    );
                                }
                            }}
                        >
                            Delete comment
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
