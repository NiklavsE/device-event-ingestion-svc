<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Factory\Howen;

/**
 * Maps Howen's short alarmCode strings to our canonical event_type vocabulary.
 *
 * Codes we haven't catalogued yet pass through lowercased so the system
 * stays functional — but they're prefixed with `howen_` to make it obvious
 * in the data that the mapping was a fallthrough and needs review.
 */
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
