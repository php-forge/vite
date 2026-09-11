<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Fixture;

use PHPForge\Vite\Asset\InlineModule;
use PHPForge\Vite\Development\InlineModuleProviderInterface;

/**
 * Inline module provider that counts how many times the resolver called it.
 *
 * Lets a test assert that every resolution runs each configured provider exactly once.
 */
final class CountingInlineModuleProviderStub implements InlineModuleProviderInterface
{
    /**
     * Number of times the resolver called the provider.
     */
    public int $calls = 0;

    /**
     * Counts the call and returns a fixed inline module.
     *
     * @param string $devServerUrl Normalized development-server URL supplied by the resolver.
     *
     * @return InlineModule Module with a fixed source.
     */
    public function provide(string $devServerUrl): InlineModule
    {
        ++$this->calls;

        return new InlineModule('window.example = true;');
    }
}
