<?php

declare(strict_types=1);

namespace PHPForge\Vite\Resolver;

use PHPForge\Vite\Asset\{AssetCollection, ModuleScript};
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Support\Url;

final readonly class DevelopmentAssetResolver implements AssetResolverInterface
{
    public function __construct(private DevelopmentConfiguration $configuration) {}

    public function resolve(array $entrypoints): AssetCollection
    {
        $assets = [];

        foreach ($this->configuration->inlineModuleProviders as $provider) {
            $assets[] = $provider->provide($this->configuration->devServerUrl);
        }

        if ($this->configuration->includeViteClient) {
            $assets[] = new ModuleScript(
                Url::join($this->configuration->devServerUrl, '@vite/client'),
            );
        }

        foreach ($entrypoints as $entrypoint) {
            $assets[] = new ModuleScript(
                Url::join($this->configuration->devServerUrl, $entrypoint),
            );
        }

        return new AssetCollection($assets);
    }
}
