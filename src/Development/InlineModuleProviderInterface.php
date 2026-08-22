<?php

declare(strict_types=1);

namespace PHPForge\Vite\Development;

use PHPForge\Vite\Asset\InlineModule;

/**
 * Provides application-owned inline modules that must run before the Vite client.
 */
interface InlineModuleProviderInterface
{
    public function provide(string $devServerUrl): InlineModule;
}
