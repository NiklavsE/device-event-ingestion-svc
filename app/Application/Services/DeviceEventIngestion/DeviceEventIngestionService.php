<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Application\Services\DeviceEventIngestion;

use DeviceEventIngestionService\Domain\Device\Interface\DeviceRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\DeviceEventAlreadyExists;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryResolver;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DeviceEventIngestionService
{
    public function __construct(
        private IncomingEventFactoryResolver $factories,
        private DeviceRepositoryInterface $devices,
        private DeviceEventRepositoryInterface $events,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(DeviceEventIngestionCommand $command): void
    {
        try {
            $incoming = $this->factories->resolve($command->protocol)->create($command->payload);
            $device   = $this->devices->ofImei($incoming->deviceImei);
            $event    = $device->recordEvent($incoming);

            $this->events->save($event);
            $this->devices->save($device);
        } catch (DeviceEventAlreadyExists $e) {
            $this->logger->info('device_event.duplicate', [
                'protocol'   => $command->protocol,
                'dedup_hash' => $e->dedupHash->value(),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('device_event.ingest_failed', [
                'protocol'  => $command->protocol,
                'exception' => $e::class,
                'message'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
