import { Link, usePage } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { projectColorClass } from '@/lib/project-colors';
import { cn } from '@/lib/utils';
import { show } from '@/routes/projects';

export function NavProjects() {
    const { currentTeam, currentTeamProjects } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();

    if (!currentTeam || currentTeamProjects.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Projects</SidebarGroupLabel>
            <SidebarMenu>
                {currentTeamProjects.map((project) => {
                    const href = show({
                        current_team: currentTeam.slug,
                        project: project.id,
                    });

                    return (
                        <SidebarMenuItem key={project.id}>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrentUrl(href)}
                                tooltip={{ children: project.name }}
                            >
                                <Link href={href} prefetch>
                                    <span
                                        className={cn(
                                            'size-2.5 shrink-0 rounded-sm',
                                            projectColorClass(project.color),
                                        )}
                                    />
                                    <span className="font-mono text-xs text-muted-foreground">
                                        {project.identifier}
                                    </span>
                                    <span className="truncate">
                                        {project.name}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
