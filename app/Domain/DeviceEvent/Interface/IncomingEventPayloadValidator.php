<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Interface;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;

interface IncomingEventPayloadValidator
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, string|array<int, string>> $rules
     *
     * @throws InvalidPayloadException
     */
    public function validate(array $payload, array $rules, string $errorMessage): void;
}
