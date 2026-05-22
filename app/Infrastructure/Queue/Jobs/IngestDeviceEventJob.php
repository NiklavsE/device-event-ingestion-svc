<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Queue\Jobs;

use DeviceEventIngestionService\Application\Services\DeviceEventIngestion\DeviceEventIngestionService;
use DeviceEventIngestionService\Application\Services\DeviceEventIngestion\DeviceEventIngestionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IngestDeviceEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly string $protocol,
        public readonly array $payload,
    ) {
    }

    public function handle(DeviceEventIngestionService $handler): void
    {
        $handler->execute(new DeviceEventIngestionRequest($this->protocol, $this->payload));
    }
}
