<?php

declare(strict_types=1);

namespace PHPForge\Vite\Configuration;

use PHPForge\Vite\Exception\ConfigurationException;
use PHPForge\Vite\Support\{Path, Url};

/**
 * Immutable configuration for Vite production-manifest resolution.
 */
final readonly class ProductionConfiguration
{
    /**
     * Normalized base URL prefixed to every emitted asset path, without a trailing separator.
     */
    public string $assetBaseUrl;

    /**
     * Validated absolute path to the Vite build manifest.
     */
    public string $manifestPath;

    /**
     * @param string $manifestPath Absolute path to the manifest emitted by the Vite build.
     * @param string $assetBaseUrl Public base URL of the build output, absolute or relative.
     * @param bool $modulePreload Whether `modulepreload` hints are emitted for transitive imports.
     *
     * @throws ConfigurationException if the manifest path is not absolute, or if the base URL is malformed,
     * protocol-relative, carries a query or fragment, or uses a scheme other than HTTP(S).
     */
    public function __construct(string $manifestPath, string $assetBaseUrl, public bool $modulePreload = true)
    {
        $this->manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');
        $this->assetBaseUrl = Url::normalizeAssetBaseUrl($assetBaseUrl);
    }

    /**
     * Creates a production-manifest configuration.
     *
     * @param string $manifestPath Absolute path to the manifest emitted by the Vite build.
     * @param string $assetBaseUrl Public base URL of the build output, absolute or relative.
     * @param bool $modulePreload Whether `modulepreload` hints are emitted for transitive imports.
     *
     * @throws ConfigurationException if the manifest path or base URL is invalid.
     *
     * @return self A new production-manifest configuration.
     */
    public static function create(
        string $manifestPath,
        string $assetBaseUrl,
        bool $modulePreload = true,
    ): self {
        return new self($manifestPath, $assetBaseUrl, $modulePreload);
    }
}
