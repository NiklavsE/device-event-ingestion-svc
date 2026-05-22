<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventTimestamp;
use PHPUnit\Framework\TestCase;

class EventTimestampTest extends TestCase
{
    public function testParsesIso8601Zulu(): void
    {
        $ts = EventTimestamp::fromIso8601('2026-05-12T10:15:30Z');

        self::assertSame('2026-05-12T10:15:30Z', $ts->toIso8601());
        self::assertSame('UTC', $ts->toDateTimeImmutable()->getTimezone()->getName());
    }

    public function testConvertsOtherZonesToUtc(): void
    {
        $ts = EventTimestamp::fromIso8601('2026-05-12T12:15:30+02:00');

        self::assertSame('2026-05-12T10:15:30Z', $ts->toIso8601());
    }

    public function testParsesUnixSeconds(): void
    {
        $ts = EventTimestamp::fromUnix(1778580930);

        self::assertSame('2026-05-12T10:15:30Z', $ts->toIso8601());
    }

    public function testAcceptsUnixZeroEpoch(): void
    {
        $ts = EventTimestamp::fromUnix(0);

        self::assertSame('1970-01-01T00:00:00Z', $ts->toIso8601());
    }

    public function testRejectsMalformedIsoString(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        EventTimestamp::fromIso8601('not-a-date');
    }

    public function testRejectsNegativeUnixSeconds(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        EventTimestamp::fromUnix(-1);
    }
}
