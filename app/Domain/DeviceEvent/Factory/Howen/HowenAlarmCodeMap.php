<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Factory\Howen;

final class HowenAlarmCodeMap
{
    private const array KNOWN = [
        'HB' => 'harsh_braking',
    ];

    public static function toEventType(string $alarmCode): string
    {
        $upper = strtoupper(trim($alarmCode));

        return self::KNOWN[$upper] ?? 'howen_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $upper) ?? 'unknown');
    }
}
