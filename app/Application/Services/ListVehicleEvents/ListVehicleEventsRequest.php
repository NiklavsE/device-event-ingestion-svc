<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Application\Services\ListVehicleEvents;

use DateTimeImmutable;

final readonly class ListVehicleEventsRequest
{
    public function __construct(
        public string $vehicleExternalId,
        public ?string $eventType = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
        public ?bool $hasMedia = null,
        public int $limit = 100,
    ) {
    }
}
