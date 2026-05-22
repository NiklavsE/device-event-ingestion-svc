<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Factory;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Domain\DeviceEvent\IncomingEvent;

interface IncomingEventFactoryInterface
{
    public function protocol(): string;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws InvalidPayloadException
     */
    public function create(array $payload): IncomingEvent;
}
