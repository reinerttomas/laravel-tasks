import type { ProjectColor } from '@/types';

// Whole class names so Tailwind's scanner keeps them; mirrors the ProjectColor PHP enum.
export const projectColors: {
    value: ProjectColor;
    label: string;
    className: string;
}[] = [
    { value: 'slate', label: 'Slate', className: 'bg-slate-500' },
    { value: 'red', label: 'Red', className: 'bg-red-500' },
    { value: 'orange', label: 'Orange', className: 'bg-orange-500' },
    { value: 'amber', label: 'Amber', className: 'bg-amber-500' },
    { value: 'green', label: 'Green', className: 'bg-green-600' },
    { value: 'teal', label: 'Teal', className: 'bg-teal-500' },
    { value: 'sky', label: 'Sky', className: 'bg-sky-500' },
    { value: 'blue', label: 'Blue', className: 'bg-blue-600' },
    { value: 'violet', label: 'Violet', className: 'bg-violet-500' },
    { value: 'pink', label: 'Pink', className: 'bg-pink-500' },
];

export function projectColorClass(color: ProjectColor): string {
    return (
        projectColors.find((item) => item.value === color)?.className ??
        'bg-slate-500'
    );
}
