<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Providers;

use DeviceEventIngestionService\Domain\DeviceEvent\Factory\CV200\CV200EventFactory;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\Howen\HowenEventFactory;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryResolver;
use Illuminate\Support\ServiceProvider;

class EventFactoryServiceProvider extends ServiceProvider
{
    /**
     * Register every supported protocol normalizer here. Adding a new
     * protocol means: (1) write a class implementing EventFactoryInterface,
     * (2) append it to this list. Nothing else changes.
     */
    private const NORMALIZERS = [
                                 CV200EventFactory::class,
                                 HowenEventFactory::class,
    ];

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
