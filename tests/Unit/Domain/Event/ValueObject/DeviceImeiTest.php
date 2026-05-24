<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\Device\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeviceImeiTest extends TestCase
{
    public function testAcceptsCanonicalImei(): void
    {
        $imei = DeviceImei::fromString('863725041234567');

        self::assertSame('863725041234567', $imei->value());
        self::assertSame('863725041234567', (string) $imei);
    }

    public function testTrimsSurroundingWhitespace(): void
    {
        self::assertSame('863725041234567', DeviceImei::fromString("  863725041234567\n")->value());
    }

    public function testEqualityIsValueBased(): void
    {
        $a = DeviceImei::fromString('863725041234567');
        $b = DeviceImei::fromString('863725041234567');
        $c = DeviceImei::fromString('863725041234568');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidImeiProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too short (13 digits)' => ['1234567890123'];
        yield 'too long (18 digits)' => ['123456789012345678'];
        yield 'contains letters' => ['86372504123456A'];
        yield 'contains dashes' => ['863-7250-4123456'];
    }

    #[DataProvider('invalidImeiProvider')]
    public function testRejectsInvalidImei(string $input): void
    {
        $this->expectException(InvalidValueObjectException::class);

        DeviceImei::fromString($input);
    }
}
