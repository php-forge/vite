<?php

declare(strict_types=1);

namespace PHPForge\Vite\Resolver;

use PHPForge\Vite\Asset\{AssetCollection, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Exception\{
    ConfigurationException,
    EntrypointNotFoundException,
    InvalidManifestException,
    ManifestNotFoundException,
    ManifestReadException,
    Message,
};
use PHPForge\Vite\Manifest\{Manifest, ManifestChunk, ManifestLoader};
use PHPForge\Vite\Support\Url;

/**
 * Resolves production assets and dependency preloads from a validated Vite manifest.
 */
final readonly class ManifestAssetResolver implements AssetResolverInterface
{
    /**
     * @param ProductionConfiguration $configuration Validated production configuration.
     * @param ManifestLoader $manifestLoader Loader providing the validated manifest.
     */
    public function __construct(
        private ProductionConfiguration $configuration,
        private ManifestLoader $manifestLoader,
    ) {}

    /**
     * Resolves the entrypoints into stylesheets, module scripts, and optional module-preload hints.
     *
     * Every entrypoint must be declared in the manifest and marked as a build entrypoint. Transitive imports are walked
     * dependency-first, their stylesheets are hoisted, and the remaining chunks become preload hints unless they are
     * already emitted as scripts or `modulePreload` is disabled. The emitted order is stylesheets, then module scripts,
     * then preloads.
     *
     * @param list<string> $entrypoints Normalized relative entrypoint paths.
     *
     * @throws ConfigurationException if the manifest path is not absolute, or a composed asset URL is unsafe.
     * @throws EntrypointNotFoundException if the manifest does not declare one of the entrypoints.
     * @throws InvalidManifestException if the manifest is malformed, or an entry is not a build entrypoint.
     * @throws ManifestNotFoundException if the configured manifest file does not exist.
     * @throws ManifestReadException if the manifest file cannot be inspected or read.
     *
     * @return AssetCollection Deduplicated production assets in emission order.
     */
    public function resolve(array $entrypoints): AssetCollection
    {
        $manifest = $this->manifestLoader->load($this->configuration->manifestPath);

        $roots = [];
        $seen = [];

        foreach ($entrypoints as $entrypoint) {
            $chunk = $manifest->get($entrypoint);

            if (!$chunk instanceof ManifestChunk) {
                throw new EntrypointNotFoundException(
                    Message::ENTRYPOINT_NOT_FOUND->getMessage(
                        $this->configuration->manifestPath,
                        $entrypoint,
                    ),
                );
            }

            if (!$chunk->isEntry()) {
                throw new InvalidManifestException(
                    Message::MANIFEST_ENTRY_NOT_ENTRYPOINT->getMessage(
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

        return new AssetCollection($this->orderedAssets($stylesheets, $scripts, $preloads));
    }

    /**
     * Prefixes an emitted build path with the configured asset base URL.
     *
     * @param string $path Build path taken from a manifest chunk.
     *
     * @return string Public URL of the asset.
     */
    private function assetUrl(string $path): string
    {
        return Url::join($this->configuration->assetBaseUrl, $path);
    }

    /**
     * Adds every stylesheet declared by a chunk to the accumulator.
     *
     * @param array<string, Stylesheet> $stylesheets Accumulator keyed by asset URL, mutated in place.
     * @param ManifestChunk $chunk Chunk whose `css` field is collected.
     *
     * @throws ConfigurationException if a composed stylesheet URL is unsafe.
     */
    private function collectCss(array &$stylesheets, ManifestChunk $chunk): void
    {
        foreach ($chunk->css() as $file) {
            $this->pushStylesheet($stylesheets, $file);
        }
    }

    /**
     * Returns transitive imports in Vite's documented dependency-first order.
     *
     * Recurses through each static import before appending the import itself, and uses `$seen` to break cycles and
     * to skip chunks already emitted as entrypoints.
     *
     * @param Manifest $manifest Manifest the references are resolved against.
     * @param ManifestChunk $chunk Chunk whose imports are walked.
     * @param array<string, true> $seen Keys already visited, mutated in place to guard against cycles.
     *
     * @return list<ManifestChunk> Imported chunks, dependencies first.
     */
    private function importedChunks(Manifest $manifest, ManifestChunk $chunk, array &$seen): array
    {
        $chunks = [];

        foreach ($chunk->imports() as $reference) {
            if (isset($seen[$reference])) {
                continue;
            }

            $seen[$reference] = true;

            /** @var ManifestChunk $import References are validated when the manifest is loaded. */
            $import = $manifest->get($reference);

            $chunks = [...$chunks, ...$this->importedChunks($manifest, $import, $seen), $import];
        }

        return $chunks;
    }

    /**
     * Yields the accumulated assets in the documented emission order.
     *
     * @param array<string, Stylesheet> $stylesheets Stylesheets keyed by asset URL.
     * @param array<string, ModuleScript> $scripts Module scripts keyed by asset URL.
     * @param array<string, ModulePreload> $preloads Module-preload hints keyed by asset URL.
     *
     * @return iterable<ModulePreload|ModuleScript|Stylesheet> Generator yielding stylesheets, then scripts, then
     * preloads.
     */
    private function orderedAssets(array $stylesheets, array $scripts, array $preloads): iterable
    {
        yield from $stylesheets;
        yield from $scripts;
        yield from $preloads;
    }

    /**
     * Records one stylesheet in the accumulator, keyed by its resolved URL to suppress duplicates.
     *
     * @param array<string, Stylesheet> $stylesheets Accumulator keyed by asset URL, mutated in place.
     * @param string $file Build path of the stylesheet.
     *
     * @throws ConfigurationException if the composed stylesheet URL is unsafe.
     */
    private function pushStylesheet(array &$stylesheets, string $file): void
    {
        $url = $this->assetUrl($file);
        $stylesheets[$url] = new Stylesheet($url);
    }
}
