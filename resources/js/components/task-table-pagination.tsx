import type { MouseEvent } from 'react';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import type { TaskTableState } from '@/hooks/use-task-table';
import { cn } from '@/lib/utils';
import type { TaskPage } from '@/types';

export default function TaskTablePagination({
    page,
    table,
}: {
    page: TaskPage;
    table: TaskTableState;
}) {
    const link = (url: string | null) => ({
        href: url ?? undefined,
        'aria-disabled': url === null,
        className: cn(url === null && 'pointer-events-none opacity-50'),
        onClick: (event: MouseEvent) => {
            event.preventDefault();

            if (url) {
                table.goTo(url);
            }
        },
    });

    return (
        <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground">
            <span data-test="task-range">
                Showing {page.from ?? 0}–{page.to ?? 0} of {page.total}
            </span>
            <Pagination className="mx-0 w-auto">
                <PaginationContent>
                    <PaginationItem>
                        <PaginationPrevious {...link(page.prevPageUrl)} />
                    </PaginationItem>
                    <PaginationItem className="px-2">
                        Page {page.currentPage} of {page.lastPage}
                    </PaginationItem>
                    <PaginationItem>
                        <PaginationNext {...link(page.nextPageUrl)} />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>
        </div>
    );
}
