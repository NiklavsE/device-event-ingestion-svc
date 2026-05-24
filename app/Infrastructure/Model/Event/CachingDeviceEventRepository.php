<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Event;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\DeviceEventAlreadyExists;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\VehicleEventQuery;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class CachingDeviceEventRepository implements DeviceEventRepositoryInterface
{
    public function __construct(
        private DeviceEventRepositoryInterface $inner,
        private CacheRepository $cache,
        private int $ttlSeconds,
        private string $keyPrefix,
    ) {
    }

    public function save(DeviceEvent $event): void
    {
        $key = $this->keyPrefix . $event->dedupHash->value();

        if (false === $this->cache->add($key, true, $this->ttlSeconds)) {
            throw new DeviceEventAlreadyExists($event->dedupHash);
        }

        try {
            $this->inner->save($event);
        } catch (DeviceEventAlreadyExists $e) {
            // We won the cache race but the DB still rejected — likely a
            // duplicate that landed before our cache entry was populated
            // (cache flush, key eviction). The cache row we just wrote
            // already short-circuits future calls; nothing else to do.
            throw $e;
        }
    }

    public function ofVehicleQuery(VehicleEventQuery $criteria): array
    {
        return $this->inner->ofVehicleQuery($criteria);
    }
}
