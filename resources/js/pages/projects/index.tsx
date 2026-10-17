import { Head, Link, router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import ArchiveProjectDialog from '@/components/archive-project-dialog';
import DeleteProjectDialog from '@/components/delete-project-dialog';
import ProjectActionsMenu from '@/components/project-actions-menu';
import ProjectFormDialog from '@/components/project-form-dialog';
import ProjectIdentifier from '@/components/project-identifier';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/projects';
import type { Project, ProjectColor } from '@/types';

type Filter = 'active' | 'archived';

type Props = {
    filter: Filter;
    projects: Project[];
    suggestedColor: ProjectColor;
};

function formatDate(date: string | null): string {
    return date ? format(new Date(date), 'd MMM yyyy') : '';
}

export default function ProjectsIndex({
    filter,
    projects,
    suggestedColor,
}: Props) {
    const { currentTeam, currentTeamPermissions } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Project | null>(null);
    const [archiving, setArchiving] = useState<Project | null>(null);
    const [deleting, setDeleting] = useState<Project | null>(null);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (project: Project) => {
        setEditing(project);
        setFormOpen(true);
    };

    const changeFilter = (value: string) => {
        if (!value || value === filter) {
            return;
        }

        router.visit(
            index(
                { current_team: teamSlug },
                value === 'archived' ? { query: { filter: value } } : {},
            ),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Projects" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Projects
                    </h1>
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        size="sm"
                        value={filter}
                        onValueChange={changeFilter}
                    >
                        <ToggleGroupItem value="active" className="px-3">
                            Active
                        </ToggleGroupItem>
                        <ToggleGroupItem value="archived" className="px-3">
                            Archived
                        </ToggleGroupItem>
                    </ToggleGroup>
                    {currentTeamPermissions?.canCreateProject && (
                        <Button
                            className="ml-auto"
                            data-test="new-project-button"
                            onClick={openCreate}
                        >
                            <Plus /> New project
                        </Button>
                    )}
                </div>

                {projects.length === 0 ? (
                    <p className="py-16 text-center text-sm text-muted-foreground">
                        No {filter} projects.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => (
                            <Card
                                key={project.id}
                                data-test="project-card"
                                className={cn(
                                    'relative gap-4 transition-shadow hover:shadow-md',
                                    project.archivedAt && 'opacity-70',
                                )}
                            >
                                <CardHeader>
                                    <div className="flex items-center gap-3 pr-8">
                                        <ProjectIdentifier
                                            identifier={project.identifier}
                                            color={project.color}
                                        />
                                        <div className="min-w-0">
                                            <CardTitle className="truncate">
                                                <Link
                                                    href={show({
                                                        current_team: teamSlug,
                                                        project: project.id,
                                                    })}
                                                    className="after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none"
                                                    prefetch
                                                >
                                                    {project.name}
                                                </Link>
                                            </CardTitle>
                                            <CardDescription className="line-clamp-1">
                                                {project.description ||
                                                    'No description'}
                                            </CardDescription>
                                        </div>
                                    </div>
                                    <div className="absolute top-4 right-4 z-10">
                                        <ProjectActionsMenu
                                            project={project}
                                            onEdit={openEdit}
                                            onArchive={setArchiving}
                                            onDelete={setDeleting}
                                        />
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    <div className="h-2 rounded-full bg-muted" />
                                    <p className="text-xs text-muted-foreground">
                                        No tasks yet
                                    </p>
                                </CardContent>
                                <CardFooter className="justify-between text-xs text-muted-foreground">
                                    <span>0 open tasks</span>
                                    {project.archivedAt ? (
                                        <Badge variant="secondary">
                                            Archived{' '}
                                            {formatDate(project.archivedAt)}
                                        </Badge>
                                    ) : (
                                        <span>
                                            Updated{' '}
                                            {formatDate(project.updatedAt)}
                                        </span>
                                    )}
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <ProjectFormDialog
                teamSlug={teamSlug}
                project={editing}
                suggestedColor={suggestedColor}
                open={formOpen}
                onOpenChange={setFormOpen}
            />
            <ArchiveProjectDialog
                teamSlug={teamSlug}
                project={archiving}
                onOpenChange={() => setArchiving(null)}
            />
            <DeleteProjectDialog
                teamSlug={teamSlug}
                project={deleting}
                onOpenChange={() => setDeleting(null)}
            />
        </>
    );
}

ProjectsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
