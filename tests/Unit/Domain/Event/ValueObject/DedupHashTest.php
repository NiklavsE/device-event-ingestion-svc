<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use PHPUnit\Framework\TestCase;

class DedupHashTest extends TestCase
{
    public function testProduces64CharHex(): void
    {
        $hash = DedupHash::fromParts('CV200', '863725041234567', 'evt_1');

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash->value());
    }

    public function testIsDeterministic(): void
    {
        $a = DedupHash::fromParts('CV200', 'x', 'y');
        $b = DedupHash::fromParts('CV200', 'x', 'y');

        self::assertSame($a->value(), $b->value());
    }

    public function testPartOrderIsSignificant(): void
    {
        $a = DedupHash::fromParts('CV200', 'x', 'y');
        $b = DedupHash::fromParts('CV200', 'y', 'x');

        self::assertNotSame($a->value(), $b->value());
    }

    public function testEmptyPartsAreRejected(): void
    {
        $this->expectException(InvalidValueObjectException::class);
        DedupHash::fromParts('CV200', '');
    }
}
