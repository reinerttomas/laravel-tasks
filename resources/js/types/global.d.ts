import type { Auth } from '@/types/auth';
import type { SidebarProject } from '@/types/projects';
import type { Team, TeamPermissions } from '@/types/teams';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            today: string;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            currentTeamPermissions: TeamPermissions | null;
            currentTeamProjects: SidebarProject[];
            teams: Team[];
            [key: string]: unknown;
        };
    }
}
