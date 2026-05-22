<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EventTypeTest extends TestCase
{
    public function testNormalisesToLowercase(): void
    {
        $type = EventType::fromString('Harsh_Braking');

        self::assertSame('harsh_braking', $type->value());
        self::assertSame('harsh_braking', (string) $type);
    }

    public function testTrimsAndAcceptsAlphanumericUnderscore(): void
    {
        self::assertSame('event_42', EventType::fromString('  event_42  ')->value());
        self::assertSame('a', EventType::fromString('A')->value());
    }

    public function testAcceptsMaxLength(): void
    {
        $sixtyFour = str_repeat('a', 64);

        self::assertSame($sixtyFour, EventType::fromString($sixtyFour)->value());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidEventTypeProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'contains hyphen' => ['harsh-braking'];
        yield 'contains space' => ['harsh braking'];
        yield 'contains punctuation' => ['harsh.braking'];
        yield 'over 64 chars' => [str_repeat('a', 65)];
    }

    #[DataProvider('invalidEventTypeProvider')]
    public function testRejectsInvalidValues(string $input): void
    {
        $this->expectException(InvalidValueObjectException::class);

        EventType::fromString($input);
    }
}
