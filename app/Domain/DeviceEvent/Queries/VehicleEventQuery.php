<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Queries;

use DateTimeImmutable;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\Vehicle\VehicleId;

final readonly class VehicleEventQuery
{
    public function __construct(
        public VehicleId $vehicleId,
        public ?EventType $eventType,
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to,
        public ?bool $hasMedia,
        public int $limit,
        public int $page = 1,
    ) {
    }
}
