<?php

declare(strict_types=1);

namespace PHPForge\Vite\Resolver;

use PHPForge\Vite\Asset\{AssetCollection, ModuleScript};
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Event\AssetsResolved;
use PHPForge\Vite\Exception\ConfigurationException;
use PHPForge\Vite\Support\Url;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Resolves development assets against a running Vite development server.
 */
final readonly class DevelopmentAssetResolver implements AssetResolverInterface
{
    /**
     * @param DevelopmentConfiguration $configuration Validated development-server configuration.
     * @param EventDispatcherInterface|null $eventDispatcher Optional dispatcher for completed resolutions.
     */
    public function __construct(
        private DevelopmentConfiguration $configuration,
        private EventDispatcherInterface|null $eventDispatcher = null,
    ) {}

    /**
     * Resolves the entrypoints into inline modules, the optional Vite client, and one module script per entrypoint.
     *
     * The emitted order is application-owned inline modules, then the Vite client when enabled, then the entrypoints in
     * the order supplied.
     *
     * @param list<string> $entrypoints Normalized relative entrypoint paths.
     *
     * @throws ConfigurationException if a composed URL is unsafe, or if an inline module provider yields empty
     * source.
     *
     * @return AssetCollection Deduplicated development assets in emission order.
     */
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

        $collection = new AssetCollection($assets);
        $this->eventDispatcher?->dispatch(new AssetsResolved($this->configuration, $entrypoints, $collection));

        return $collection;
    }
}
