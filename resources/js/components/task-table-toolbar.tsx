import { Search, X } from 'lucide-react';
import type { FacetOption } from '@/components/faceted-filter';
import FacetedFilter from '@/components/faceted-filter';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { TaskTableState } from '@/hooks/use-task-table';
import { taskPriorities, taskStatuses } from '@/lib/tasks';
import type { TaskFacet, TaskFacetCounts, TaskFilters } from '@/types';

type Props = {
    table: TaskTableState;
    filters: TaskFilters;
    counts: TaskFacetCounts;
    /** The third facet: assignees on a project task list, projects on My tasks. */
    extraFacet: {
        facet: Extract<TaskFacet, 'assignee' | 'project'>;
        title: string;
        options: FacetOption[];
    };
    searchPlaceholder: string;
};

export default function TaskTableToolbar({
    table,
    filters,
    counts,
    extraFacet,
    searchPlaceholder,
}: Props) {
    return (
        <div className="flex flex-wrap items-center gap-2">
            <div className="relative w-full sm:w-64">
                <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    name="search"
                    value={table.search}
                    onChange={(event) => table.setSearch(event.target.value)}
                    placeholder={searchPlaceholder}
                    aria-label="Search tasks"
                    className="h-8 pl-8"
                />
            </div>
            <FacetedFilter
                title="Status"
                options={taskStatuses}
                selected={filters.status}
                counts={counts.status}
                onChange={(values) => table.setFacet('status', values)}
            />
            <FacetedFilter
                title="Priority"
                options={taskPriorities}
                selected={filters.priority}
                counts={counts.priority}
                onChange={(values) => table.setFacet('priority', values)}
            />
            <FacetedFilter
                title={extraFacet.title}
                options={extraFacet.options}
                selected={filters[extraFacet.facet]}
                counts={counts[extraFacet.facet]}
                onChange={(values) => table.setFacet(extraFacet.facet, values)}
            />
            {table.isFiltered && (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 px-2"
                    onClick={table.reset}
                    data-test="reset-filters"
                >
                    Reset
                    <X />
                </Button>
            )}
        </div>
    );
}
