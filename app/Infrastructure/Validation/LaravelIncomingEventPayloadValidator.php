<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Validation;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\IncomingEventPayloadValidator;
use Illuminate\Contracts\Validation\Factory;

final readonly class LaravelIncomingEventPayloadValidator implements IncomingEventPayloadValidator
{
    public function __construct(private Factory $factory)
    {
    }

    public function validate(array $payload, array $rules, string $errorMessage): void
    {
        $validator = $this->factory->make($payload, $rules);
        if ($validator->fails()) {
            /** @var array<string, array<int, string>> $errors */
            $errors = $validator->errors()->toArray();
            throw new InvalidPayloadException($errorMessage, $errors);
        }
    }
}
