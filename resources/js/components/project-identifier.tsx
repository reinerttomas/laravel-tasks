import { projectColorClass } from '@/lib/project-colors';
import { cn } from '@/lib/utils';
import type { ProjectColor } from '@/types';

type Props = {
    identifier: string;
    color: ProjectColor;
    className?: string;
};

export default function ProjectIdentifier({
    identifier,
    color,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex size-10 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-semibold text-white',
                projectColorClass(color),
                className,
            )}
        >
            {identifier || '—'}
        </div>
    );
}
