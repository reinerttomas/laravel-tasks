export type ProjectColor =
    | 'slate'
    | 'red'
    | 'orange'
    | 'amber'
    | 'green'
    | 'teal'
    | 'sky'
    | 'blue'
    | 'violet'
    | 'pink';

export type Project = {
    id: string;
    name: string;
    identifier: string;
    color: ProjectColor;
    description: string | null;
    archivedAt: string | null;
    updatedAt: string | null;
};

export type SidebarProject = Pick<
    Project,
    'id' | 'name' | 'identifier' | 'color'
>;
