<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\Vehicle\VehicleId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VehicleIdTest extends TestCase
{
    public function testAcceptsTypicalPlate(): void
    {
        $id = VehicleId::fromString('LV-1234');

        self::assertSame('LV-1234', $id->value());
        self::assertSame('LV-1234', (string) $id);
    }

    public function testTrimsWhitespace(): void
    {
        self::assertSame('LV-1234', VehicleId::fromString("  LV-1234\t")->value());
    }

    public function testAcceptsBoundaryLength(): void
    {
        $boundary = str_repeat('a', 64);

        self::assertSame($boundary, VehicleId::fromString($boundary)->value());
    }

    public function testEqualityIsValueBased(): void
    {
        $a = VehicleId::fromString('LV-1234');
        $b = VehicleId::fromString('LV-1234');
        $c = VehicleId::fromString('LV-9999');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidVehicleIdProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'over the 64-char cap' => [str_repeat('x', 65)];
    }

    #[DataProvider('invalidVehicleIdProvider')]
    public function testRejectsInvalidVehicleId(string $input): void
    {
        $this->expectException(InvalidValueObjectException::class);

        VehicleId::fromString($input);
    }
}
