<?php

declare(strict_types=1);

namespace Tests;

trait PayloadFixtures
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function cv200Payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'protocol'    => 'CV200',
            'device_imei' => '863725041234567',
            'vehicle_id'  => 'LV-1234',
            'event_id'    => 'evt_20260512_00001',
            'event_type'  => 'harsh_braking',
            'timestamp'   => '2026-05-12T10:15:30Z',
            'gps'         => ['lat' => 56.9496, 'lng' => 24.1052, 'speed' => 74, 'heading' => 182],
            'camera'      => [
                'channel'          => 2,
                'media_type'       => 'video',
                'file_name'        => '20260512_101530_CH2.mp4',
                'duration_seconds' => 18,
                'codec'            => 'h264',
            ],
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function howenPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'protocol'  => 'HOWEN',
            'imei'      => '863725041234567',
            'plate'     => 'LV-1234',
            'alarmCode' => 'HB',
            'alarmTime' => 1778580930,
            'location'  => ['latitude' => 56.9496, 'longitude' => 24.1052, 'speedKmh' => 74],
            'video'     => ['ch' => 2, 'name' => '20260512_101530_CH2.mp4', 'len' => 18, 'format' => 'h264'],
        ], $overrides);
    }
}
