<?php

declare(strict_types=1);

namespace PHPForge\Vite\Manifest;

use function str_ends_with;
use function strtolower;

/**
 * Immutable representation of one Vite manifest chunk.
 */
final readonly class ManifestChunk
{
    /**
     * @param string $key Manifest key under which the entry is declared.
     * @param string $file Emitted build file, relative to the asset base URL.
     * @param string|null $src Original source path, or `null` for chunks Vite generated without one.
     * @param list<string> $css Stylesheets emitted alongside this chunk.
     * @param list<string> $assets Static assets referenced by this chunk.
     * @param bool $isEntry Whether the chunk is a build entrypoint.
     * @param string|null $name Chunk name, or `null` when Vite emitted none.
     * @param bool $isDynamicEntry Whether the chunk is the target of a dynamic import.
     * @param list<string> $imports Statically imported chunk keys.
     * @param list<string> $dynamicImports Dynamically imported chunk keys.
     */
    public function __construct(
        public string $key,
        public string $file,
        public string|null $src = null,
        public array $css = [],
        public array $assets = [],
        public bool $isEntry = false,
        public string|null $name = null,
        public bool $isDynamicEntry = false,
        public array $imports = [],
        public array $dynamicImports = [],
    ) {}

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
}
