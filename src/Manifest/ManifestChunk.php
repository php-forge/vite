<?php

declare(strict_types=1);

namespace PHPForge\Vite\Manifest;

use function str_ends_with;
use function strtolower;

/**
 * Immutable representation of one Vite manifest chunk.
 */
final class ManifestChunk
{
    /**
     * Static assets referenced by this chunk.
     *
     * @var list<string>
     */
    private array $assets = [];

    /**
     * Stylesheets emitted alongside this chunk.
     *
     * @var list<string>
     */
    private array $css = [];

    /**
     * Dynamically imported chunk keys.
     *
     * @var list<string>
     */
    private array $dynamicImports = [];

    /**
     * Statically imported chunk keys.
     *
     * @var list<string>
     */
    private array $imports = [];

    /**
     * Whether the chunk is the target of a dynamic import.
     */
    private bool $isDynamicEntry = false;

    /**
     * Whether the chunk is a build entrypoint.
     */
    private bool $isEntry = false;

    /**
     * Chunk name, or `null` when Vite emitted none.
     */
    private string|null $name = null;

    /**
     * Original source path, or `null` for chunks Vite generated without one.
     */
    private string|null $src = null;

    /**
     * @param string $key Manifest key under which the entry is declared.
     * @param string $file Emitted build file, relative to the asset base URL.
     */
    public function __construct(public readonly string $key, public readonly string $file) {}

    /**
     * Returns the static assets referenced by this chunk.
     *
     * @return list<string> Static asset paths in manifest order.
     */
    public function assets(): array
    {
        return $this->assets;
    }

    /**
     * Creates a manifest chunk from its required values.
     *
     * @param string $key Manifest key under which the entry is declared.
     * @param string $file Emitted build file, relative to the asset base URL.
     *
     * @return ManifestChunk A new manifest chunk with the optional values set to their defaults.
     */
    public static function create(string $key, string $file): self
    {
        return new self($key, $file);
    }

    /**
     * Returns the stylesheets emitted alongside this chunk.
     *
     * @return list<string> Stylesheet paths in manifest order.
     */
    public function css(): array
    {
        return $this->css;
    }

    /**
     * Returns the dynamically imported chunk keys.
     *
     * @return list<string> Dynamic import references in manifest order.
     */
    public function dynamicImports(): array
    {
        return $this->dynamicImports;
    }

    /**
     * Returns the statically imported chunk keys.
     *
     * @return list<string> Static import references in manifest order.
     */
    public function imports(): array
    {
        return $this->imports;
    }

    /**
     * Detects whether the emitted file is a stylesheet rather than a JavaScript module.
     *
     * The check is a case-insensitive test on the `.css` extension of the emitted file.
     *
     * @return bool `true` when the chunk emits a stylesheet.
     */
    public function isCss(): bool
    {
        return str_ends_with(strtolower($this->file), '.css');
    }

    /**
     * Returns whether the chunk is the target of a dynamic import.
     *
     * @return bool Whether the chunk is a dynamic entrypoint.
     */
    public function isDynamicEntry(): bool
    {
        return $this->isDynamicEntry;
    }

    /**
     * Returns whether the chunk is a build entrypoint.
     *
     * @return bool Whether the chunk is a build entrypoint.
     */
    public function isEntry(): bool
    {
        return $this->isEntry;
    }

    /**
     * Returns the chunk name emitted by Vite.
     *
     * @return string|null Chunk name, or `null` when Vite emitted none.
     */
    public function name(): string|null
    {
        return $this->name;
    }

    /**
     * Returns the original source path emitted by Vite.
     *
     * @return string|null Original source path, or `null` when Vite emitted none.
     */
    public function src(): string|null
    {
        return $this->src;
    }

    /**
     * Returns a new chunk with the static asset paths replaced.
     *
     * @param list<string> $assets Static assets referenced by this chunk.
     *
     * @return ManifestChunk A new chunk containing the supplied static assets.
     */
    public function withAssets(array $assets): self
    {
        $clone = clone $this;
        $clone->assets = $assets;

        return $clone;
    }

    /**
     * Returns a new chunk with the stylesheet paths replaced.
     *
     * @param list<string> $css Stylesheets emitted alongside this chunk.
     *
     * @return ManifestChunk A new chunk containing the supplied stylesheet paths.
     */
    public function withCss(array $css): self
    {
        $clone = clone $this;
        $clone->css = $css;

        return $clone;
    }

    /**
     * Returns a new chunk with its dynamic-entry status replaced.
     *
     * @param bool $isDynamicEntry Whether the chunk is the target of a dynamic import.
     *
     * @return ManifestChunk A new chunk with the supplied dynamic-entry status.
     */
    public function withDynamicEntry(bool $isDynamicEntry = true): self
    {
        $clone = clone $this;
        $clone->isDynamicEntry = $isDynamicEntry;

        return $clone;
    }

    /**
     * Returns a new chunk with the dynamic import references replaced.
     *
     * @param list<string> $dynamicImports Dynamically imported chunk keys.
     *
     * @return ManifestChunk A new chunk containing the supplied dynamic import references.
     */
    public function withDynamicImports(array $dynamicImports): self
    {
        $clone = clone $this;
        $clone->dynamicImports = $dynamicImports;

        return $clone;
    }

    /**
     * Returns a new chunk with its entrypoint status replaced.
     *
     * @param bool $isEntry Whether the chunk is a build entrypoint.
     *
     * @return ManifestChunk A new chunk with the supplied entrypoint status.
     */
    public function withEntry(bool $isEntry = true): self
    {
        $clone = clone $this;
        $clone->isEntry = $isEntry;

        return $clone;
    }

    /**
     * Returns a new chunk with the static import references replaced.
     *
     * @param list<string> $imports Statically imported chunk keys.
     *
     * @return ManifestChunk A new chunk containing the supplied static import references.
     */
    public function withImports(array $imports): self
    {
        $clone = clone $this;
        $clone->imports = $imports;

        return $clone;
    }

    /**
     * Returns a new chunk with its name replaced.
     *
     * @param string|null $name Chunk name, or `null` when Vite emitted none.
     *
     * @return ManifestChunk A new chunk with the supplied name.
     */
    public function withName(string|null $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * Returns a new chunk with its original source path replaced.
     *
     * @param string|null $src Original source path, or `null` when Vite emitted none.
     *
     * @return ManifestChunk A new chunk with the supplied source path.
     */
    public function withSrc(string|null $src): self
    {
        $clone = clone $this;
        $clone->src = $src;

        return $clone;
    }
}
