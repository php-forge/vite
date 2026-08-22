<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Fixtures;

use PHPForge\Vite\Asset\InlineModule;
use PHPForge\Vite\Development\InlineModuleProviderInterface;

final class CapturingInlineModuleProviderStub implements InlineModuleProviderInterface
{
    public string|null $receivedUrl = null;

    public function provide(string $devServerUrl): InlineModule
    {
        $this->receivedUrl = $devServerUrl;

        return new InlineModule("window.devServer = \"$devServerUrl\";");
    }
}
