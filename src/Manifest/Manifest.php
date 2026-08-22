<?php

declare(strict_types=1);

namespace PHPForge\Vite\Manifest;

/**
 * Immutable representation of a validated Vite build manifest.
 */
final readonly class Manifest
{
    /**
     * @param array<string, ManifestChunk> $chunks
     */
    public function __construct(private array $chunks) {}

    /**
     * @return array<string, ManifestChunk>
     */
    public function chunks(): array
    {
        return $this->chunks;
    }

    public function get(string $key): ManifestChunk|null
    {
        return $this->chunks[$key] ?? null;
    }
}
