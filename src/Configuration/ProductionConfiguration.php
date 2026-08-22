<?php

declare(strict_types=1);

namespace PHPForge\Vite\Configuration;

use PHPForge\Vite\Support\{Path, Url};

/**
 * Immutable configuration for Vite production-manifest resolution.
 */
final readonly class ProductionConfiguration
{
    public string $assetBaseUrl;
    public string $manifestPath;

    public function __construct(
        string $manifestPath,
        string $assetBaseUrl,
        public bool $modulePreload = true,
    ) {
        $this->manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');
        $this->assetBaseUrl = Url::normalizeAssetBaseUrl($assetBaseUrl);
    }
}
