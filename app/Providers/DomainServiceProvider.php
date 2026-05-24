<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Providers;

use DeviceEventIngestionService\Domain\Device\Interface\DeviceRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\IncomingEventPayloadValidator;
use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceRepository;
use DeviceEventIngestionService\Infrastructure\Model\Event\CachingDeviceEventRepository;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventRepository;
use DeviceEventIngestionService\Infrastructure\Validation\LaravelIncomingEventPayloadValidator;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeviceRepositoryInterface::class, EloquentDeviceRepository::class);
        $this->app->singleton(IncomingEventPayloadValidator::class, LaravelIncomingEventPayloadValidator::class);

        $this->app->singleton(DeviceEventRepositoryInterface::class, function ($app) {
            $config = (array) $app['config']->get('ingestion.dedup', []);

            return new CachingDeviceEventRepository(
                $app->make(EloquentDeviceEventRepository::class),
                $app->make(CacheFactory::class)->store(),
                (int) ($config['ttl_seconds'] ?? 86400),
                (string) ($config['key_prefix'] ?? 'dedup:event:'),
            );
        });
    }
}
