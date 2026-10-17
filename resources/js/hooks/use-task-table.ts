import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { TaskFacet, TaskFilters, TaskSortColumn } from '@/types';

const SEARCH_DEBOUNCE_MS = 300;

/**
 * Keeps the task table state in the query string: every change visits the page URL again,
 * preserving component state so open popovers and the search input survive the reload.
 */
export function useTaskTable(url: string, filters: TaskFilters) {
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
                status: next.status,
                priority: next.priority,
                assignee: next.assignee,
                project: next.project,
                sort: next.sort === '-number' ? undefined : next.sort,
            },
            { preserveState: true, preserveScroll: true, replace: true },
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
        visit({
            search: '',
            status: [],
            priority: [],
            assignee: [],
            project: [],
        });
    };

    const isFiltered =
        filters.search !== '' ||
        filters.status.length > 0 ||
        filters.priority.length > 0 ||
        filters.assignee.length > 0 ||
        filters.project.length > 0;

    const goTo = (pageUrl: string) =>
        router.get(pageUrl, {}, { preserveState: true, preserveScroll: true });

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
