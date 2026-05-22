<?php

declare(strict_types=1);

namespace Tests\Feature;

use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use DeviceEventIngestionService\Ui\Queue\IngestDeviceEventJob;
use Illuminate\Support\Facades\Redis;
use Mockery\MockInterface;
use RuntimeException;
use Tests\FeatureTestCase;
use Tests\PayloadFixtures;

/**
 * Integration test that runs the job through a real Redis-backed queue and
 * a real worker (`queue:work --once`). The suite default stays `sync`; this
 * file opts into `redis` in setUp() so we can observe the production retry
 * machinery — `FailOnException` middleware, `failed_jobs` writes, delayed
 * retry queue placement — that sync mode short-circuits.
 */
class IngestionWorkerRedisQueueTest extends FeatureTestCase
{
    use PayloadFixtures;

    private const string QUEUE             = 'default';
    private const string REDIS_LIST_KEY    = 'queues:' . self::QUEUE;
    private const string REDIS_DELAYED_KEY = 'queues:' . self::QUEUE . ':delayed';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('queue.default', 'redis');

        // Flush the isolated test DB (REDIS_DB=15 from phpunit.xml). Without
        // this, jobs left over from previous runs would be processed first.
        Redis::connection()->flushdb();
    }

    public function testPermanentFailureLandsInFailedJobsAfterOneAttempt(): void
    {
        // No device commissioned → DeviceNotFoundException, in the fail-fast
        // set on IngestDeviceEventJob::middleware().
        IngestDeviceEventJob::dispatch('CV200', $this->cv200Payload());

        $this->artisan('queue:work', [
            '--once'     => true,
            '--max-time' => 10,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('failed_jobs', 1);
        self::assertSame(0, Redis::connection()->llen(self::REDIS_LIST_KEY), 'main queue should be empty');
        self::assertSame(0, Redis::connection()->zcard(self::REDIS_DELAYED_KEY), 'no retry must be scheduled');
    }

    public function testTransientFailureGoesToDelayedQueueForRetry(): void
    {
        $this->createDevice();

        // RuntimeException is NOT in the fail-fast set, so the job should
        // be re-queued with backoff rather than failed.
        $this->mock(
            DeviceEventRepositoryInterface::class,
            fn (MockInterface $m) => $m->shouldReceive('save')
                ->once()
                ->andThrow(new RuntimeException('transient db blip')),
        );

        IngestDeviceEventJob::dispatch('CV200', $this->cv200Payload());

        $this->artisan('queue:work', [
            '--once'     => true,
            '--max-time' => 10,
        ]);

        $this->assertDatabaseCount('failed_jobs', 0);
        self::assertSame(1, Redis::connection()->zcard(self::REDIS_DELAYED_KEY), 'job should be on the delayed retry queue');
    }
}
