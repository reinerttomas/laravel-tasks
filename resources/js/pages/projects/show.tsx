import { Head, usePage } from '@inertiajs/react';
import { Archive, ArchiveRestore, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import ArchiveProjectDialog from '@/components/archive-project-dialog';
import CreateTaskDialog from '@/components/create-task-dialog';
import ProjectFormDialog from '@/components/project-form-dialog';
import TaskTable from '@/components/task-table';
import TaskTablePagination from '@/components/task-table-pagination';
import TaskTableToolbar from '@/components/task-table-toolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTaskTable } from '@/hooks/use-task-table';
import { index, show } from '@/routes/projects';
import type {
    Project,
    TaskFacetCounts,
    TaskFilters,
    TaskPage,
    AssignableMember,
} from '@/types';

type Props = {
    project: Project;
    tasks: TaskPage;
    facets: TaskFacetCounts;
    filters: TaskFilters;
    members: AssignableMember[];
};

export default function ProjectsShow({
    project,
    tasks,
    facets,
    filters,
    members,
}: Props) {
    const { currentTeam, currentTeamPermissions } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const archived = project.archivedAt !== null;

    const [editing, setEditing] = useState(false);
    const [archiving, setArchiving] = useState(false);
    const [creatingTask, setCreatingTask] = useState(false);

    const table = useTaskTable(
        show.url({ current_team: teamSlug, project: project.id }),
        filters,
    );

    return (
        <>
            <Head title={project.name} />

            <div className="flex flex-1 flex-col gap-4 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2">
                            <h1 className="truncate text-2xl font-semibold tracking-tight">
                                {project.name}
                            </h1>
                            <Badge variant="outline" className="font-mono">
                                {project.identifier}
                            </Badge>
                            {archived && (
                                <Badge variant="secondary">Archived</Badge>
                            )}
                        </div>
                        {project.description && (
                            <p className="text-sm text-muted-foreground">
                                {project.description}
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {currentTeamPermissions?.canUpdateProject &&
                            !archived && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditing(true)}
                                >
                                    <Pencil /> Edit project
                                </Button>
                            )}
                        {currentTeamPermissions?.canArchiveProject && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setArchiving(true)}
                            >
                                {archived ? (
                                    <>
                                        <ArchiveRestore /> Restore
                                    </>
                                ) : (
                                    <>
                                        <Archive /> Archive
                                    </>
                                )}
                            </Button>
                        )}
                        {currentTeamPermissions?.canCreateTask && !archived && (
                            <Button
                                size="sm"
                                onClick={() => setCreatingTask(true)}
                                data-test="new-task-button"
                            >
                                <Plus /> New task
                            </Button>
                        )}
                    </div>
                </div>

                <TaskTableToolbar
                    table={table}
                    filters={filters}
                    counts={facets}
                    searchPlaceholder={`Search title or ${project.identifier}-12…`}
                    extraFacet={{
                        facet: 'assignee',
                        title: 'Assignee',
                        options: [
                            { value: 'unassigned', label: 'Unassigned' },
                            ...members.map((member) => ({
                                value: member.id,
                                label: member.name,
                            })),
                        ],
                    }}
                />

                <TaskTable
                    tasks={tasks.data}
                    table={table}
                    context="assignee"
                />

                <TaskTablePagination page={tasks} table={table} />
            </div>

            <ProjectFormDialog
                teamSlug={teamSlug}
                project={project}
                suggestedColor={project.color}
                open={editing}
                onOpenChange={setEditing}
            />
            <ArchiveProjectDialog
                teamSlug={teamSlug}
                project={archiving ? project : null}
                onOpenChange={() => setArchiving(false)}
            />
            <CreateTaskDialog
                teamSlug={teamSlug}
                project={project}
                members={members}
                open={creatingTask}
                onOpenChange={setCreatingTask}
            />
        </>
    );
}

ProjectsShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    project: Project;
}) => {
    const teamSlug = props.currentTeam?.slug ?? '';

    return {
        breadcrumbs: [
            { title: 'Projects', href: index(teamSlug) },
            {
                title: props.project.name,
                href: show({
                    current_team: teamSlug,
                    project: props.project.id,
                }),
            },
        ],
    };
};
