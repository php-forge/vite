<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use PHPForge\Vite\Support\Url;

final readonly class ModulePreload implements AssetInterface
{
    public string $url;

    public function __construct(string $url)
    {
        $this->url = Url::requireSafeAssetUrl($url);
    }
}
