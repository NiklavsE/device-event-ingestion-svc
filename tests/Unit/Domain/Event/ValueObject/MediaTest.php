<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\Media;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function testStoresAllProvidedFields(): void
    {
        $media = Media::create(
            channel: 2,
            fileName: '20260512_101530_CH2.mp4',
            durationSeconds: 18,
            codec: 'h264',
            mediaType: 'video',
        );

        self::assertSame(2, $media->channel());
        self::assertSame('20260512_101530_CH2.mp4', $media->fileName());
        self::assertSame(18, $media->durationSeconds());
        self::assertSame('h264', $media->codec());
        self::assertSame('video', $media->mediaType());
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $media = Media::create(channel: null, fileName: 'minimal.mp4');

        self::assertNull($media->channel());
        self::assertNull($media->durationSeconds());
        self::assertNull($media->codec());
        self::assertNull($media->mediaType());
    }

    public function testTrimsWhitespaceFromFileName(): void
    {
        self::assertSame('a.mp4', Media::create(null, '  a.mp4 ')->fileName());
    }

    public function testToArraySerialisesToSnakeCase(): void
    {
        $media = Media::create(1, 'x.mp4', 5, 'h264', 'video');

        self::assertSame([
            'channel'          => 1,
            'file_name'        => 'x.mp4',
            'duration_seconds' => 5,
            'codec'            => 'h264',
            'media_type'       => 'video',
        ], $media->toArray());
    }

    public function testRejectsEmptyFileName(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        Media::create(null, '   ');
    }

    public function testRejectsNegativeDuration(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        Media::create(null, 'x.mp4', -1);
    }
}
