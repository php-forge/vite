<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use PHPForge\Vite\Exception\ConfigurationException;
use PHPForge\Vite\Support\Url;

/**
 * Represents a validated stylesheet asset URL.
 */
final readonly class Stylesheet implements AssetInterface
{
    /**
     * Absolute or relative URL of the stylesheet to link.
     */
    public string $url;

    /**
     * @param string $url Asset URL emitted as the `href` of a `stylesheet` link.
     *
     * @throws ConfigurationException if the URL is empty, malformed, protocol-relative, or uses a scheme other than
     * HTTP(S).
     */
    public function __construct(string $url)
    {
        $this->url = Url::requireSafeAssetUrl($url);
    }
}
