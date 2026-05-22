<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Event;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\DeviceEventAlreadyExists;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\VehicleEventQuery;
use Psr\SimpleCache\CacheInterface;
use Throwable;

final readonly class CachingDeviceEventRepository implements DeviceEventRepositoryInterface
{
    public function __construct(
        private DeviceEventRepositoryInterface $inner,
        private CacheInterface $cache,
        private int $ttlSeconds,
        private string $keyPrefix,
    ) {
    }

    public function save(DeviceEvent $event): void
    {
        $key = $this->keyPrefix . $event->dedupHash->value();

        if ($this->cache->has($key)) {
            throw new DeviceEventAlreadyExists($event->dedupHash);
        }

        try {
            $this->inner->save($event);
        } catch (DeviceEventAlreadyExists $e) {
            // Cache missed but the DB caught the duplicate. Record it so
            // the next call short-circuits at the cache.
            $this->cache->set($key, true, $this->ttlSeconds);
            throw $e;
        } catch (Throwable $e) {
            throw $e;
        }

        $this->cache->set($key, true, $this->ttlSeconds);
    }

    public function ofVehicleQuery(VehicleEventQuery $criteria): array
    {
        return $this->inner->ofVehicleQuery($criteria);
    }
}
