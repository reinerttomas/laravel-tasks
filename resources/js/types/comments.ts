import type { AssignableMember } from './tasks';

export type TaskComment = {
    id: string;
    body: string;
    author: AssignableMember | null;
    createdAt: string | null;
    updatedAt: string | null;
    can: { update: boolean; delete: boolean };
};
