<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Factory;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\UnsupportedProtocolException;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryInterface;

final class IncomingEventFactoryResolver
{
    /** @var array<string, IncomingEventFactoryInterface> */
    private array $normalizers = [];

    public function register(IncomingEventFactoryInterface $normalizer): void
    {
        $this->normalizers[$this->canonicalize($normalizer->protocol())] = $normalizer;
    }

    public function resolve(string $protocol): IncomingEventFactoryInterface
    {
        $key = $this->canonicalize($protocol);
        if (false === isset($this->normalizers[$key])) {
            throw new UnsupportedProtocolException("Unsupported protocol: {$protocol}");
        }

        return $this->normalizers[$key];
    }

    /** @return list<string> */
    public function supported(): array
    {
        return array_keys($this->normalizers);
    }

    private function canonicalize(string $protocol): string
    {
        return strtoupper(trim($protocol));
    }
}
