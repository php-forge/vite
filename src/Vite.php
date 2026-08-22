<?php

declare(strict_types=1);

namespace PHPForge\Vite;

use PHPForge\Vite\Asset\AssetCollection;
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Manifest\ManifestLoader;
use PHPForge\Vite\Resolver\{AssetResolverInterface, DevelopmentAssetResolver, ManifestAssetResolver};
use PHPForge\Vite\Support\EntrypointNormalizer;

/**
 * Framework-agnostic facade for resolving Vite assets.
 */
final readonly class Vite
{
    /**
     * @var list<string>
     */
    private array $entrypoints;
    private ManifestLoader $manifestLoader;
    private string|null $manifestPath;
    private AssetResolverInterface $resolver;

    /**
     * @param list<string> $entrypoints
     */
    public function __construct(
        DevelopmentConfiguration|ProductionConfiguration $configuration,
        array $entrypoints = [],
        ManifestLoader|null $manifestLoader = null,
    ) {
        $this->manifestLoader = $manifestLoader ?? new ManifestLoader();

        $this->entrypoints = EntrypointNormalizer::normalize($entrypoints, false);

        if ($configuration instanceof DevelopmentConfiguration) {
            $this->manifestPath = null;

            $this->resolver = new DevelopmentAssetResolver($configuration);

            return;
        }

        $this->manifestPath = $configuration->manifestPath;

        $this->resolver = new ManifestAssetResolver($configuration, $this->manifestLoader);
    }

    public function clearManifestCache(): void
    {
        if ($this->manifestPath !== null) {
            $this->manifestLoader->clear($this->manifestPath);
        }
    }

    /**
     * @param list<mixed>|string|null $entrypoints
     */
    public function resolve(array|string|null $entrypoints = null): AssetCollection
    {
        $entrypoints = EntrypointNormalizer::normalize($entrypoints ?? $this->entrypoints, true);

        return $this->resolver->resolve($entrypoints);
    }
}
