<?php

declare(strict_types=1);

namespace PHPForge\Vite\Resolver;

use PHPForge\Vite\Asset\{AssetCollection, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Exception\{EntrypointNotFoundException, InvalidManifestException};
use PHPForge\Vite\Manifest\{Manifest, ManifestChunk, ManifestLoader};
use PHPForge\Vite\Support\Url;

use function array_values;
use function sprintf;

final readonly class ManifestAssetResolver implements AssetResolverInterface
{
    public function __construct(
        private ProductionConfiguration $configuration,
        private ManifestLoader $manifestLoader,
    ) {}

    public function resolve(array $entrypoints): AssetCollection
    {
        $manifest = $this->manifestLoader->load($this->configuration->manifestPath);

        $roots = [];
        $seen = [];

        foreach ($entrypoints as $entrypoint) {
            $chunk = $manifest->get($entrypoint);

            if (!$chunk instanceof ManifestChunk) {
                throw new EntrypointNotFoundException(
                    sprintf(
                        'The Vite manifest file "%s" does not contain the entrypoint "%s".',
                        $this->configuration->manifestPath,
                        $entrypoint,
                    ),
                );
            }

            if (!$chunk->isEntry) {
                throw new InvalidManifestException(
                    sprintf(
                        'The Vite manifest entry "%s" in "%s" is not marked as an entrypoint.',
                        $entrypoint,
                        $this->configuration->manifestPath,
                    ),
                );
            }

            $roots[] = $chunk;
            $seen[$entrypoint] = true;
        }

        $stylesheets = [];
        $scripts = [];
        $importedChunks = [];

        foreach ($roots as $root) {
            $imports = $this->importedChunks($manifest, $root, $seen);

            $this->collectCss($stylesheets, $root);

            foreach ($imports as $import) {
                $this->collectCss($stylesheets, $import);

                if ($import->isCss()) {
                    $this->pushStylesheet($stylesheets, $import->file);
                }

                $importedChunks[] = $import;
            }

            if ($root->isCss()) {
                $this->pushStylesheet($stylesheets, $root->file);
            } else {
                $url = $this->assetUrl($root->file);

                $scripts[$url] = new ModuleScript($url);
            }
        }

        $preloads = [];

        if ($this->configuration->modulePreload) {
            foreach ($importedChunks as $import) {
                if ($import->isCss()) {
                    continue;
                }

                $url = $this->assetUrl($import->file);

                if (isset($scripts[$url])) {
                    continue;
                }

                $preloads[$url] = new ModulePreload($url);
            }
        }

        return new AssetCollection([
            ...array_values($stylesheets),
            ...array_values($scripts),
            ...array_values($preloads),
        ]);
    }

    private function assetUrl(string $path): string
    {
        return Url::join($this->configuration->assetBaseUrl, $path);
    }

    /**
     * @param array<string, Stylesheet> $stylesheets
     */
    private function collectCss(array &$stylesheets, ManifestChunk $chunk): void
    {
        foreach ($chunk->css as $file) {
            $this->pushStylesheet($stylesheets, $file);
        }
    }

    /**
     * Returns transitive imports in Vite's documented dependency-first order.
     *
     * @param array<string, true> $seen
     *
     * @return list<ManifestChunk>
     */
    private function importedChunks(Manifest $manifest, ManifestChunk $chunk, array &$seen): array
    {
        $chunks = [];

        foreach ($chunk->imports as $reference) {
            if (isset($seen[$reference])) {
                continue;
            }

            $seen[$reference] = true;
            $import = $manifest->get($reference);

            if (!$import instanceof ManifestChunk) {
                throw new InvalidManifestException(
                    sprintf(
                        'The Vite manifest entry "%s" in "%s" references missing chunk "%s".',
                        $chunk->key,
                        $this->configuration->manifestPath,
                        $reference,
                    ),
                );
            }

            $chunks = [...$chunks, ...$this->importedChunks($manifest, $import, $seen), $import];
        }

        return $chunks;
    }

    /**
     * @param array<string, Stylesheet> $stylesheets
     */
    private function pushStylesheet(array &$stylesheets, string $file): void
    {
        $url = $this->assetUrl($file);
        $stylesheets[$url] = new Stylesheet($url);
    }
}
