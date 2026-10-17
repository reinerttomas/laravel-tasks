import { format, formatDistanceToNow, parseISO } from 'date-fns';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/** "3 days ago", with the absolute date and time in a tooltip. */
export default function RelativeTime({ date }: { date: string }) {
    const parsed = parseISO(date);

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <time dateTime={date}>
                    {formatDistanceToNow(parsed, { addSuffix: true })}
                </time>
            </TooltipTrigger>
            <TooltipContent>
                {format(parsed, 'd MMM yyyy, HH:mm')}
            </TooltipContent>
        </Tooltip>
    );
}
