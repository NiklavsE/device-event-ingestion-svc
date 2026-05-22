<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Providers;

use DeviceEventIngestionService\Domain\DeviceEvent\Factory\CV200\CV200EventFactory;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\Howen\HowenEventFactory;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryResolver;
use Illuminate\Support\ServiceProvider;

class EventFactoryServiceProvider extends ServiceProvider
{
    private const NORMALIZERS = [CV200EventFactory::class, HowenEventFactory::class];

    public function register(): void
    {
        $this->app->singleton(IncomingEventFactoryResolver::class, function ($app) {
            $registry = new IncomingEventFactoryResolver();
            foreach (self::NORMALIZERS as $class) {
                $registry->register($app->make($class));
            }

            return $registry;
        });
    }
}
