<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Exception;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use RuntimeException;

final class DeviceEventAlreadyExists extends RuntimeException
{
    public function __construct(public readonly DedupHash $dedupHash)
    {
        parent::__construct(
            sprintf('Device event with dedup hash %s is already on record.', $dedupHash->value()),
        );
    }
}
