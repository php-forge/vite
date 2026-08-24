<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Fixture;

use PHPForge\Vite\Asset\InlineModule;
use PHPForge\Vite\Development\InlineModuleProviderInterface;

/**
 * Inline module provider that records the development-server URL it was called with.
 *
 * Lets a test assert both the emitted module source and the URL the resolver passed to the provider.
 */
final class CapturingInlineModuleProviderStub implements InlineModuleProviderInterface
{
    /**
     * Development-server URL captured on the last call, or `null` while the provider is untouched.
     */
    public string|null $receivedUrl = null;

    /**
     * Records the supplied URL and returns an inline module that echoes it back.
     *
     * @param string $devServerUrl Normalized development-server URL supplied by the resolver.
     *
     * @return InlineModule Module assigning the captured URL to a global.
     */
    public function provide(string $devServerUrl): InlineModule
    {
        $this->receivedUrl = $devServerUrl;

        return new InlineModule("window.devServer = \"$devServerUrl\";");
    }
}
