<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Exception;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use RuntimeException;

/**
 * Raised by EventRepositoryInterface::save() when the event being persisted
 * is a duplicate of one already on record (matched by dedup_hash). Both
 * EventRepositoryInterface implementations — the Eloquent persister and
 * the caching decorator — surface duplicates via this single exception so
 * the application layer treats them uniformly, regardless of which tier
 * (cache or DB UNIQUE) actually detected the collision.
 */
final class DeviceEventAlreadyExists extends RuntimeException
{
    public function __construct(public readonly DedupHash $dedupHash)
    {
        parent::__construct(
            sprintf('Device event with dedup hash %s is already on record.', $dedupHash->value()),
        );
    }
}
