<?php

declare(strict_types=1);

namespace PHPForge\Vite\Manifest;

/**
 * Immutable representation of a validated Vite build manifest.
 */
final readonly class Manifest
{
    /**
     * @param array<string, ManifestChunk> $chunks Validated chunks indexed by their manifest key.
     */
    public function __construct(private array $chunks) {}

    /**
     * Returns every chunk declared by the manifest.
     *
     * @return array<string, ManifestChunk> Chunks indexed by their manifest key.
     */
    public function chunks(): array
    {
        return $this->chunks;
    }

    /**
     * Looks up a single chunk by its manifest key.
     *
     * @param string $key Manifest key, usually the entrypoint source path.
     *
     * @return ManifestChunk|null The matching chunk, or `null` when the manifest declares no such key.
     */
    public function get(string $key): ManifestChunk|null
    {
        return $this->chunks[$key] ?? null;
    }
}
