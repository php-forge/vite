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
     * @param list<string> $css
     * @param list<string> $assets
     * @param list<string> $imports
     * @param list<string> $dynamicImports
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

    public function isCss(): bool
    {
        return str_ends_with(strtolower($this->file), '.css');
    }
}
