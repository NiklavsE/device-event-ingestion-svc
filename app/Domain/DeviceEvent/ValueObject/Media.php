<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;

final readonly class Media
{
    private function __construct(
        private ?int    $channel,
        private string  $fileName,
        private ?int    $durationSeconds,
        private ?string $codec,
        private ?string $mediaType,
    ) {
    }

    public static function create(
        ?int $channel,
        string $fileName,
        ?int $durationSeconds = null,
        ?string $codec = null,
        ?string $mediaType = null,
    ): self {
        $fileName = trim($fileName);
        if ($fileName === '') {
            throw new InvalidValueObjectException('Media file name cannot be empty');
        }
        if ($durationSeconds !== null && $durationSeconds < 0) {
            throw new InvalidValueObjectException("Media duration must be non-negative: {$durationSeconds}");
        }

        return new self($channel, $fileName, $durationSeconds, $codec, $mediaType);
    }

    public function channel(): ?int
    {
        return $this->channel;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function durationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function codec(): ?string
    {
        return $this->codec;
    }

    public function mediaType(): ?string
    {
        return $this->mediaType;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'channel'          => $this->channel,
            'file_name'        => $this->fileName,
            'duration_seconds' => $this->durationSeconds,
            'codec'            => $this->codec,
            'media_type'       => $this->mediaType,
        ];
    }
}
