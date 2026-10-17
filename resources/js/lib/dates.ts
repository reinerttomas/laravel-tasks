import { format, parseISO } from 'date-fns';

export function formatDate(date: Date | string | null): string {
    if (date === null) {
        return '';
    }

    return format(
        typeof date === 'string' ? parseISO(date) : date,
        'd MMM yyyy',
    );
}
