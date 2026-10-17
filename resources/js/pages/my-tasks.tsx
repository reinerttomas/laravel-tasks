import { Head, usePage } from '@inertiajs/react';
import TaskTable from '@/components/task-table';
import TaskTablePagination from '@/components/task-table-pagination';
import TaskTableToolbar from '@/components/task-table-toolbar';
import { useTaskTable } from '@/hooks/use-task-table';
import { projectColorClass } from '@/lib/project-colors';
import { openTaskStatuses } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { myTasks } from '@/routes';
import type { TaskFacetCounts, TaskFilters, TaskPage } from '@/types';

type Props = {
    tasks: TaskPage;
    facets: TaskFacetCounts;
    filters: TaskFilters;
};

export default function MyTasks({ tasks, facets, filters }: Props) {
    const { currentTeam, currentTeamProjects } = usePage().props;

    const table = useTaskTable(myTasks.url(currentTeam?.slug ?? ''), filters, {
        defaultStatuses: openTaskStatuses,
    });

    return (
        <>
            <Head title="My tasks" />

            <div className="flex flex-1 flex-col gap-4 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        My tasks
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Tasks assigned to you across the team's projects.
                    </p>
                </div>

                <TaskTableToolbar
                    table={table}
                    filters={filters}
                    counts={facets}
                    searchPlaceholder="Search title or reference…"
                    extraFacet={{
                        facet: 'project',
                        title: 'Project',
                        options: currentTeamProjects.map((project) => ({
                            value: project.id,
                            label: project.name,
                            prefix: (
                                <span
                                    className={cn(
                                        'size-2.5 shrink-0 rounded-sm',
                                        projectColorClass(project.color),
                                    )}
                                />
                            ),
                        })),
                    }}
                />

                <TaskTable tasks={tasks.data} table={table} context="project" />

                <TaskTablePagination page={tasks} table={table} />
            </div>
        </>
    );
}

MyTasks.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'My tasks',
            href: myTasks(props.currentTeam?.slug ?? ''),
        },
    ],
});
