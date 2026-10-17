import { Head } from '@inertiajs/react';
import ProjectIdentifier from '@/components/project-identifier';
import { Badge } from '@/components/ui/badge';
import { index, show } from '@/routes/projects';
import type { Project } from '@/types';

type Props = {
    project: Project;
};

export default function ProjectsShow({ project }: Props) {
    return (
        <>
            <Head title={project.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <ProjectIdentifier
                        identifier={project.identifier}
                        color={project.color}
                    />
                    <div className="min-w-0">
                        <div className="flex items-center gap-2">
                            <h1 className="truncate text-2xl font-semibold tracking-tight">
                                {project.name}
                            </h1>
                            {project.archivedAt && (
                                <Badge variant="secondary">Archived</Badge>
                            )}
                        </div>
                        {project.description && (
                            <p className="text-sm text-muted-foreground">
                                {project.description}
                            </p>
                        )}
                    </div>
                </div>

                <p className="py-16 text-center text-sm text-muted-foreground">
                    No tasks yet
                </p>
            </div>
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
