<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Exception;

use DomainException;
use Throwable;

class InvalidPayloadException extends DomainException
{
    /**
     * @param array<string, string|list<string>> $errors  field => message(s)
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** @return array<string, string|list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
