<?php

declare(strict_types=1);

namespace PHPForge\Vite;

use PHPForge\Vite\Asset\AssetCollection;
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Exception\{
    ConfigurationException,
    EntrypointNotFoundException,
    InvalidEntrypointException,
    InvalidManifestException,
    ManifestNotFoundException,
    ManifestReadException,
};
use PHPForge\Vite\Manifest\ManifestLoader;
use PHPForge\Vite\Resolver\{AssetResolverInterface, DevelopmentAssetResolver, ManifestAssetResolver};
use PHPForge\Vite\Support\EntrypointNormalizer;

/**
 * Resolves framework-neutral Vite assets for development-server or production-manifest configuration.
 */
final readonly class Vite
{
    /**
     * @var list<string> Default entrypoints used whenever {@see Vite::resolve()} is called without an override.
     */
    private array $entrypoints;

    /**
     * Loader backing the production resolver, retained so its cache can be cleared.
     */
    private ManifestLoader $manifestLoader;

    /**
     * Absolute manifest path under production configuration, or `null` under development configuration.
     */
    private string|null $manifestPath;

    /**
     * Resolver selected from the supplied configuration.
     */
    private AssetResolverInterface $resolver;

    /**
     * Selects the resolution strategy implied by the supplied configuration.
     *
     * A {@see DevelopmentConfiguration} resolves against a running development server and keeps no manifest state;
     * a {@see ProductionConfiguration} resolves through the build manifest. The default entrypoints are validated
     * eagerly but may be empty, since {@see Vite::resolve()} accepts a per-call override.
     *
     * @param DevelopmentConfiguration|ProductionConfiguration $configuration Configuration selecting the strategy.
     * @param list<mixed> $entrypoints Default entrypoints to validate and resolve when no override is supplied.
     * @param ManifestLoader|null $manifestLoader Loader to share across instances, or `null` to create one.
     *
     * @throws InvalidEntrypointException if a default entrypoint is not a `string`, is empty, or contains a
     * backslash or a control character.
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

    /**
     * Drops the cached manifest so the next resolution re-reads it from disk.
     *
     * Does nothing under development configuration, where no manifest is loaded.
     */
    public function clearManifestCache(): void
    {
        if ($this->manifestPath !== null) {
            $this->manifestLoader->clear($this->manifestPath);
        }
    }

    /**
     * Creates a facade using the supplied configuration and default entrypoints.
     *
     * @param DevelopmentConfiguration|ProductionConfiguration $configuration Configuration selecting the strategy.
     * @param list<mixed> $entrypoints Default entrypoints to validate and resolve when no override is supplied.
     * @param ManifestLoader|null $manifestLoader Loader to share across instances, or `null` to create one.
     *
     * @throws InvalidEntrypointException if a default entrypoint is not a `string`, is empty, or contains a
     * backslash or a control character.
     *
     * @return Vite A new facade using the supplied configuration.
     */
    public static function create(
        DevelopmentConfiguration|ProductionConfiguration $configuration,
        array $entrypoints = [],
        ManifestLoader|null $manifestLoader = null,
    ): self {
        return new self($configuration, $entrypoints, $manifestLoader);
    }

    /**
     * Resolves entrypoints into the framework-neutral assets a page must load.
     *
     * At least one entrypoint is required at this point, whether it comes from the override or from the defaults
     * supplied to the constructor.
     *
     * @param list<mixed>|string|null $entrypoints Single entrypoint or list overriding the configured defaults, or
     * `null` to resolve those defaults.
     *
     * @throws ConfigurationException if a resolved asset URL is unsafe, or the manifest path is not absolute.
     * @throws EntrypointNotFoundException if the manifest does not declare one of the entrypoints.
     * @throws InvalidEntrypointException if an entrypoint is invalid, or none is configured.
     * @throws InvalidManifestException if the manifest is malformed, or an entry is not a build entrypoint.
     * @throws ManifestNotFoundException if the configured manifest file does not exist.
     * @throws ManifestReadException if the manifest file cannot be inspected or read.
     *
     * @return AssetCollection Deduplicated assets in the order the page is expected to emit.
     */
    public function resolve(array|string|null $entrypoints = null): AssetCollection
    {
        $entrypoints = EntrypointNormalizer::normalize($entrypoints ?? $this->entrypoints, true);

        return $this->resolver->resolve($entrypoints);
    }
}
