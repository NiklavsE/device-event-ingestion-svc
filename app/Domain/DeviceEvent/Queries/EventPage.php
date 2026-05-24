<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Queries;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;

final readonly class EventPage
{
    /** @param array<int, DeviceEvent> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function lastPage(): int
    {
        if ($this->perPage <= 0 || $this->total <= 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->perPage);
    }
}
