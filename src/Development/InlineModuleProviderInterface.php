<?php

declare(strict_types=1);

namespace PHPForge\Vite\Development;

use PHPForge\Vite\Asset\InlineModule;
use PHPForge\Vite\Exception\ConfigurationException;

/**
 * Provides application-owned inline modules that must run before the Vite client.
 */
interface InlineModuleProviderInterface
{
    /**
     * Builds the inline module to emit ahead of the Vite client and the configured entrypoints.
     *
     * Implementations own the module source; the package only positions the result in the resolved asset order.
     *
     * @param string $devServerUrl Normalized development-server URL, without a trailing separator.
     *
     * @throws ConfigurationException if the produced source is empty or contains only whitespace.
     *
     * @return InlineModule Inline module rendered before the Vite client.
     */
    public function provide(string $devServerUrl): InlineModule;
}
