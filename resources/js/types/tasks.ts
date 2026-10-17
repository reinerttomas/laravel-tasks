import type { ProjectColor } from './projects';

export type TaskStatus =
    | 'backlog'
    | 'todo'
    | 'in_progress'
    | 'done'
    | 'canceled';

export type TaskPriority = 'none' | 'urgent' | 'high' | 'medium' | 'low';

export type TaskCounts = Record<TaskStatus, number>;

export type AssignableMember = {
    id: string;
    name: string;
};

export type TaskRow = {
    id: string;
    number: number;
    reference: string;
    title: string;
    status: TaskStatus;
    priority: TaskPriority;
    project: { id: string; identifier: string; color: ProjectColor };
    assignee: AssignableMember | null;
    dueDate: string | null;
    updatedAt: string | null;
};

export type TaskPage = {
    data: TaskRow[];
    from: number | null;
    to: number | null;
    total: number;
    currentPage: number;
    lastPage: number;
    prevPageUrl: string | null;
    nextPageUrl: string | null;
};

export type TaskFacet = 'status' | 'priority' | 'assignee' | 'project';

export type TaskSortColumn =
    | 'number'
    | 'title'
    | 'status'
    | 'priority'
    | 'due_date'
    | 'updated_at';

/** Mirrors the table's query string; `sort` is a column, prefixed with "-" for descending. */
export type TaskFilters = {
    search: string;
    status: string[];
    priority: string[];
    assignee: string[];
    project: string[];
    sort: string;
};

/** Task counts per facet value, with every filter applied except the facet's own. */
export type TaskFacetCounts = Partial<
    Record<TaskFacet, Record<string, number>>
>;

/** A task as shown on its detail page; `author` is null once their account is deleted. */
export type TaskDetail = {
    id: string;
    reference: string;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    project: {
        id: string;
        name: string;
        identifier: string;
        color: ProjectColor;
        archivedAt: string | null;
    };
    assignee: AssignableMember | null;
    author: AssignableMember | null;
    dueDate: string | null;
    createdAt: string | null;
    updatedAt: string | null;
};
