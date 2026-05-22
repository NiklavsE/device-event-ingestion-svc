<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\GeoPoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeoPointTest extends TestCase
{
    public function testStoresLatLngWithOptionalSpeedAndHeading(): void
    {
        $point = GeoPoint::create(56.9496, 24.1052, 74.0, 182);

        self::assertSame(56.9496, $point->latitude());
        self::assertSame(24.1052, $point->longitude());
        self::assertSame(74.0, $point->speed());
        self::assertSame(182, $point->heading());
    }

    public function testSpeedAndHeadingAreOptional(): void
    {
        $point = GeoPoint::create(0.0, 0.0);

        self::assertNull($point->speed());
        self::assertNull($point->heading());
    }

    public function testAcceptsCoordinateBoundaries(): void
    {
        $sw = GeoPoint::create(-90.0, -180.0);
        $ne = GeoPoint::create(90.0, 180.0);

        self::assertSame(-90.0, $sw->latitude());
        self::assertSame(180.0, $ne->longitude());
    }

    public function testAcceptsHeadingBoundaries(): void
    {
        self::assertSame(0, GeoPoint::create(0.0, 0.0, null, 0)->heading());
        self::assertSame(360, GeoPoint::create(0.0, 0.0, null, 360)->heading());
    }

    /** @return iterable<string, array{float, float, ?float, ?int}> */
    public static function invalidGeoPointProvider(): iterable
    {
        yield 'lat below -90' => [-90.0001, 0.0, null, null];
        yield 'lat above 90' => [90.0001, 0.0, null, null];
        yield 'lng below -180' => [0.0, -180.0001, null, null];
        yield 'lng above 180' => [0.0, 180.0001, null, null];
        yield 'negative speed' => [0.0, 0.0, -0.1, null];
        yield 'heading below 0' => [0.0, 0.0, null, -1];
        yield 'heading above 360' => [0.0, 0.0, null, 361];
    }

    #[DataProvider('invalidGeoPointProvider')]
    public function testRejectsOutOfRangeValues(float $lat, float $lng, ?float $speed, ?int $heading): void
    {
        $this->expectException(InvalidValueObjectException::class);

        GeoPoint::create($lat, $lng, $speed, $heading);
    }
}
