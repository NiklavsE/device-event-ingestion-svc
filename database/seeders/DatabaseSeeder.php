<?php

declare(strict_types=1);

namespace Database\Seeders;

use DeviceEventIngestionService\Application\Services\DeviceEventIngestion\DeviceEventIngestionRequest;
use DeviceEventIngestionService\Application\Services\DeviceEventIngestion\DeviceEventIngestionService;
use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Vehicle\EloquentVehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(DeviceEventIngestionService $handler): void
    {
        // 1. Register the vehicles.
        EloquentVehicleModel::query()->updateOrCreate(
            ['external_id' => 'LV-1234'],
            ['label'       => 'Demo Truck #1'],
        );
        EloquentVehicleModel::query()->updateOrCreate(
            ['external_id' => 'LV-9999'],
            ['label'       => 'Demo Truck #2'],
        );

        // 2. Commission the devices on those vehicles.
        EloquentDeviceModel::query()->firstOrCreate(
            ['imei'                => '863725041234567'],
            ['vehicle_external_id' => 'LV-1234'],
        );
        EloquentDeviceModel::query()->firstOrCreate(
            ['imei'                => '863725041234568'],
            ['vehicle_external_id' => 'LV-9999'],
        );

        // 3. Ingest events against the commissioned fleet.
        $base       = Carbon::parse('2026-05-01T08:00:00Z');
        $eventTypes = ['harsh_braking', 'harsh_acceleration', 'speeding', 'panic'];

        foreach (range(1, 12) as $i) {
            $ts = $base->copy()->addHours($i * 6);

            $payload = [
                'protocol'    => 'CV200',
                'device_imei' => '863725041234567',
                'vehicle_id'  => 'LV-1234',
                'event_id'    => "evt_seed_{$i}",
                'event_type'  => $eventTypes[$i % count($eventTypes)],
                'timestamp'   => $ts->toIso8601ZuluString(),
                'gps'         => ['lat' => 56.9496, 'lng' => 24.1052, 'speed' => 50 + $i, 'heading' => 180],
            ];

            if ($i % 2 === 0) {
                $payload['camera'] = [
                    'channel'          => 2,
                    'media_type'       => 'video',
                    'file_name'        => sprintf('seed_%02d.mp4', $i),
                    'duration_seconds' => 10 + $i,
                    'codec'            => 'h264',
                ];
            }

            $handler->execute(new DeviceEventIngestionRequest('CV200', $payload));
        }

        // One Howen entry showing the cross-protocol normalisation.
        $handler->execute(new DeviceEventIngestionRequest('HOWEN', [
            'protocol'  => 'HOWEN',
            'imei'      => '863725041234568',
            'plate'     => 'LV-9999',
            'alarmCode' => 'HB',
            'alarmTime' => $base->copy()->addDay()->timestamp,
            'location'  => ['latitude' => 56.95, 'longitude' => 24.11, 'speedKmh' => 80],
            'video'     => ['ch' => 1, 'name' => 'howen_seed.mp4', 'len' => 12, 'format' => 'h264'],
        ]));
    }
}
