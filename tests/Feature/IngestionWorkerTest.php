<?php

declare(strict_types=1);

namespace Tests\Feature;

use DeviceEventIngestionService\Domain\Device\Exception\DeviceNotFoundException;
use DeviceEventIngestionService\Domain\Device\Exception\VehicleMismatchException;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventMediaModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventModel;
use DeviceEventIngestionService\Ui\Queue\IngestDeviceEventJob;
use Tests\FeatureTestCase;
use Tests\PayloadFixtures;

class IngestionWorkerTest extends FeatureTestCase
{
    use PayloadFixtures;

    public function testCv200PayloadIsNormalisedAndPersisted(): void
    {
        $this->createDevice();

        IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload());

        self::assertSame(1, EloquentDeviceModel::count(), 'should not auto-create devices');
        self::assertSame(1, EloquentDeviceEventModel::count());
        self::assertSame(1, EloquentDeviceEventMediaModel::count());

        $this->assertMatchesNormalizedShape('CV200', $this->soleStoredEvent());
    }

    public function testHowenPayloadIsNormalisedToTheSameInternalShape(): void
    {
        $this->createDevice();

        IngestDeviceEventJob::dispatchSync('HOWEN', $this->howenPayload());

        $this->assertMatchesNormalizedShape('HOWEN', $this->soleStoredEvent());
    }

    public function testThrowsForUnregisteredDevice(): void
    {
        $this->expectException(DeviceNotFoundException::class);

        try {
            IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload());
        } finally {
            self::assertSame(0, EloquentDeviceModel::count(), 'must not auto-create devices');
            self::assertSame(0, EloquentDeviceEventModel::count());
        }
    }

    public function testThrowsWhenPayloadVehicleDoesNotMatchInstallation(): void
    {
        $this->createDevice(vehicleExternalId: 'LV-1234');

        $this->expectException(VehicleMismatchException::class);

        try {
            IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload(['vehicle_id' => 'LV-9999']));
        } finally {
            self::assertSame(0, EloquentDeviceEventModel::count());
        }
    }

    public function testThrowsForCv200MissingRequiredField(): void
    {
        $this->createDevice();
        $payload = $this->cv200Payload();
        unset($payload['device_imei']);

        $this->expectException(InvalidPayloadException::class);

        IngestDeviceEventJob::dispatchSync('CV200', $payload);
    }

    public function testThrowsForHowenMissingAlarmcode(): void
    {
        $this->createDevice();
        $payload = $this->howenPayload();
        unset($payload['alarmCode']);

        $this->expectException(InvalidPayloadException::class);

        IngestDeviceEventJob::dispatchSync('HOWEN', $payload);
    }

    public function testDuplicateIsHandledIdempotently(): void
    {
        $this->createDevice();

        IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload());
        IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload());

        self::assertSame(1, EloquentDeviceEventModel::count(), 'duplicate must not create a second row');
    }

    public function testDuplicateDetectedWhenOnlyDbConstraintCatchesIt(): void
    {
        $this->createDevice();

        IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload());

        $this->forgetDedupCache();

        IngestDeviceEventJob::dispatchSync('CV200', $this->cv200Payload());

        self::assertSame(1, EloquentDeviceEventModel::count());
    }

    /**
     * Asserts the stored event matches the canonical normalized shape from
     * the assignment spec. Both CV200 and Howen payloads must produce
     * identical output here apart from the `protocol` field — that's the
     * whole point of having a normalization layer.
     */
    private function assertMatchesNormalizedShape(string $expectedProtocol, EloquentDeviceEventModel $stored): void
    {
        $expected = [
            'protocol'        => $expectedProtocol,
            'device_imei'     => '863725041234567',
            'vehicle_id'      => 'LV-1234',
            'event_type'      => 'harsh_braking',
            'event_timestamp' => '2026-05-12T10:15:30Z',
            'latitude'        => 56.9496,
            'longitude'       => 24.1052,
            'speed'           => 74.0,
            'media'           => [
                'channel'          => 2,
                'file_name'        => '20260512_101530_CH2.mp4',
                'duration_seconds' => 18,
                'codec'            => 'h264',
            ],
        ];

        $actual = [
            'protocol'        => $stored->protocol,
            'device_imei'     => $stored->device?->imei,
            'vehicle_id'      => $stored->vehicle_external_id,
            'event_type'      => $stored->event_type,
            'event_timestamp' => $stored->event_timestamp->format('Y-m-d\TH:i:s\Z'),
            'latitude'        => $stored->latitude,
            'longitude'       => $stored->longitude,
            'speed'           => $stored->speed,
            'media'           => [
                'channel'          => $stored->media?->channel,
                'file_name'        => $stored->media?->file_name,
                'duration_seconds' => $stored->media?->duration_seconds,
                'codec'            => $stored->media?->codec,
            ],
        ];

        self::assertSame($expected, $actual, 'Stored event must match the assignment normalization schema');
    }

    private function soleStoredEvent(): EloquentDeviceEventModel
    {
        return EloquentDeviceEventModel::query()->with(['device', 'media'])->sole();
    }
}
