<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Application\Services\DeviceEventIngestion;

final readonly class DeviceEventIngestionCommand
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $protocol,
        public array $payload,
    ) {
    }
}
