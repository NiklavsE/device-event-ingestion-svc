<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Queue;

use DeviceEventIngestionService\Application\Services\DeviceEventIngestion\DeviceEventIngestionCommand;
use DeviceEventIngestionService\Application\Services\DeviceEventIngestion\DeviceEventIngestionService;
use DeviceEventIngestionService\Domain\Device\Exception\DeviceNotFoundException;
use DeviceEventIngestionService\Domain\Device\Exception\VehicleMismatchException;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\UnsupportedProtocolException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\FailOnException;
use Illuminate\Queue\SerializesModels;

final class IngestDeviceEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly string $protocol,
        public readonly array $payload,
    ) {
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        // Permanent failures — retries can't recover, so skip straight to
        // failed_jobs instead of burning the retry budget on a poison payload.
        return [
            new FailOnException([
                InvalidPayloadException::class,
                UnsupportedProtocolException::class,
                DeviceNotFoundException::class,
                VehicleMismatchException::class,
            ]),
        ];
    }

    public function handle(DeviceEventIngestionService $handler): void
    {
        $handler->execute(new DeviceEventIngestionCommand($this->protocol, $this->payload));
    }
}
