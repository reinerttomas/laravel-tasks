import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type {
    TaskFacet,
    TaskFilters,
    TaskSortColumn,
    TaskStatus,
} from '@/types';

const SEARCH_DEBOUNCE_MS = 300;

const visitOptions = {
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

/**
 * Keeps the task table state in the query string: every change visits the page URL again,
 * preserving component state so open popovers and the search input survive the reload.
 */
export function useTaskTable(
    url: string,
    filters: TaskFilters,
    { defaultStatuses = [] }: { defaultStatuses?: TaskStatus[] } = {},
) {
    const [search, setSearchInput] = useState(filters.search);
    const timeout = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => () => clearTimeout(timeout.current), []);

    const visit = (changes: Partial<TaskFilters>) => {
        const next = { ...filters, search, ...changes };

        // Any change of filters or sorting starts again from the first page.
        router.get(
            url,
            {
                search: next.search || undefined,
                // A page with default statuses applies them when `status` is missing, so "every status" is sent as `status[]=`.
                status:
                    defaultStatuses.length > 0 && next.status.length === 0
                        ? ['']
                        : next.status,
                priority: next.priority,
                assignee: next.assignee,
                project: next.project,
                sort: sortParameter(next.sort),
            },
            visitOptions,
        );
    };

    const setSearch = (value: string) => {
        setSearchInput(value);
        clearTimeout(timeout.current);
        timeout.current = setTimeout(
            () => visit({ search: value }),
            SEARCH_DEBOUNCE_MS,
        );
    };

    const setFacet = (facet: TaskFacet, values: string[]) =>
        visit({ [facet]: values });

    const sortColumn = filters.sort.replace(/^-/, '') as TaskSortColumn;
    const sortDescending = filters.sort.startsWith('-');

    const toggleSort = (column: TaskSortColumn) =>
        visit({
            sort:
                column === sortColumn && !sortDescending
                    ? `-${column}`
                    : column,
        });

    const reset = () => {
        clearTimeout(timeout.current);
        setSearchInput('');
        router.get(url, { sort: sortParameter(filters.sort) }, visitOptions);
    };

    const isFiltered =
        filters.search !== '' ||
        !sameValues(filters.status, defaultStatuses) ||
        filters.priority.length > 0 ||
        filters.assignee.length > 0 ||
        filters.project.length > 0;

    const goTo = (pageUrl: string) => router.get(pageUrl, {}, visitOptions);

    return {
        search,
        setSearch,
        setFacet,
        sortColumn,
        sortDescending,
        toggleSort,
        reset,
        isFiltered,
        goTo,
    };
}

export type TaskTableState = ReturnType<typeof useTaskTable>;

function sortParameter(sort: string): string | undefined {
    return sort === '-number' ? undefined : sort;
}

function sameValues(values: string[], expected: string[]): boolean {
    return (
        values.length === expected.length &&
        expected.every((value) => values.includes(value))
    );
}
