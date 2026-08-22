<?php

declare(strict_types=1);

namespace PHPForge\Vite\Configuration;

use PHPForge\Vite\Support\{EntrypointNormalizer, Path, Url};

/**
 * Immutable configuration for Vite production-manifest resolution.
 */
final readonly class ProductionConfiguration
{
    public string $assetBaseUrl;
    /**
     * @var list<string>
     */
    public array $entrypoints;
    public string $manifestPath;

    /**
     * @param list<string> $entrypoints
     */
    public function __construct(
        string $manifestPath,
        string $assetBaseUrl,
        array $entrypoints = [],
        public bool $modulePreload = true,
    ) {
        $this->manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');
        $this->assetBaseUrl = Url::normalizeAssetBaseUrl($assetBaseUrl);
        $this->entrypoints = EntrypointNormalizer::normalize($entrypoints, false);
    }
}
