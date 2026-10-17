<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * One page of the task table with what the footer needs to show the range and link the neighbouring pages.
 */
final readonly class TaskPage
{
    /**
     * @param  list<TaskRow>  $data
     */
    public function __construct(
        public array $data,
        public ?int $from,
        public ?int $to,
        public int $total,
        public int $currentPage,
        public int $lastPage,
        public ?string $prevPageUrl,
        public ?string $nextPageUrl,
    ) {
        //
    }

    /**
     * Create the page from the paginated tasks.
     *
     * @param  LengthAwarePaginator<int, Task>  $tasks
     */
    public static function fromPaginator(LengthAwarePaginator $tasks): self
    {
        return new self(
            data: array_values(array_map(TaskRow::fromTask(...), $tasks->items())),
            from: $tasks->firstItem(),
            to: $tasks->lastItem(),
            total: $tasks->total(),
            currentPage: $tasks->currentPage(),
            lastPage: $tasks->lastPage(),
            prevPageUrl: $tasks->previousPageUrl(),
            nextPageUrl: $tasks->nextPageUrl(),
        );
    }
}
