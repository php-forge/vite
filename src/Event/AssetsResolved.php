<?php

declare(strict_types=1);

namespace PHPForge\Vite\Event;

use PHPForge\Vite\Asset\AssetCollection;
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Manifest\Manifest;

/**
 * Announces a completed asset resolution without reading or resolving the assets again.
 */
final readonly class AssetsResolved
{
    /**
     * @param list<string> $entrypoints Actual normalized entrypoints for this resolution.
     * @param Manifest|null $manifest The manifest already used by the resolver, or `null` in development mode.
     */
    public function __construct(
        public DevelopmentConfiguration|ProductionConfiguration $configuration,
        public array $entrypoints,
        public AssetCollection $assets,
        public Manifest|null $manifest = null,
    ) {}
}
