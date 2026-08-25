<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{AssetCollection, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Configuration\ProductionConfiguration;
use PHPForge\Vite\Exception\{EntrypointNotFoundException, InvalidManifestException, Message};
use PHPForge\Vite\Manifest\ManifestLoader;
use PHPForge\Vite\Vite;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Vite} production manifest asset resolution.
 */
#[Group('production')]
final class ViteProductionTest extends TestCase
{
    public function testAbsoluteBaseUrlProducesCdnAssetUrls(): void
    {
        $configuration = new ProductionConfiguration(
            manifestPath: __DIR__ . '/Fixture/css-entrypoint-manifest.json',
            assetBaseUrl: 'https://cdn.example.com/build/',
        );

        $stylesheets = (new Vite($configuration, ['resources/css/app.css']))->resolve()->stylesheets();

        self::assertSame(
            ['https://cdn.example.com/build/assets/app-abc123.css'],
            array_map(static fn(Stylesheet $stylesheet): string => $stylesheet->url, $stylesheets),
            'The asset URL must use the configured CDN base.',
        );
    }

    public function testCircularImportDoesNotPreloadRootEntrypoint(): void
    {
        $assets = $this->vite('circular-import-manifest.json', ['resources/js/app.js'])->resolve();

        self::assertSame(
            [
                ModuleScript::class . ':/build/assets/app-circular.js',
                ModulePreload::class . ':/build/assets/chunk-b.js',
                ModulePreload::class . ':/build/assets/chunk-a.js',
            ],
            $this->describe($assets),
            'The cyclic root must be omitted while imported chunks remain ordered.',
        );
    }

    public function testClearManifestCacheDiscardsTheProductionManifest(): void
    {
        $manifestPath = __DIR__ . '/Fixture/manifest.json';
        $loader = new ManifestLoader();
        $cachedManifest = $loader->load($manifestPath);
        $vite = new Vite(
            new ProductionConfiguration($manifestPath, '/build'),
            ['views/foo.js'],
            $loader,
        );

        $vite->clearManifestCache();

        self::assertNotSame(
            $cachedManifest,
            $loader->load($manifestPath),
            'Clearing through the facade must discard the production manifest.',
        );
    }

    public function testCssEntrypointProducesOnlyStylesheet(): void
    {
        $assets = $this->vite('css-entrypoint-manifest.json', ['resources/css/app.css'])->resolve();

        self::assertSame(
            [Stylesheet::class . ':/build/assets/app-abc123.css'],
            $this->describe($assets),
            'A CSS entrypoint must not produce a module script.',
        );
    }

    public function testDynamicImportsAreNotIncludedInInitialAssets(): void
    {
        $assets = $this->vite('manifest.json', ['views/bar.js'])->resolve();

        self::assertStringNotContainsString(
            'baz-B2H3sXNv.js',
            implode('\n', $this->describe($assets)),
            'A dynamic import must not be loaded initially.',
        );
    }

    public function testEmptyBaseUrlProducesRelativeAssetUrls(): void
    {
        $configuration = new ProductionConfiguration(
            manifestPath: __DIR__ . '/Fixture/css-entrypoint-manifest.json',
            assetBaseUrl: '',
        );

        $stylesheets = (new Vite($configuration, ['resources/css/app.css']))->resolve()->stylesheets();

        self::assertSame(
            ['assets/app-abc123.css'],
            array_map(static fn(Stylesheet $stylesheet): string => $stylesheet->url, $stylesheets),
            'An empty base must preserve a relative asset URL.',
        );
    }

    public function testEntrypointsWithSameOutputFileAreDeduplicated(): void
    {
        $assets = $this->vite(
            'duplicate-file-manifest.json',
            ['resources/js/app.js', 'resources/js/app-legacy.js'],
        )->resolve();

        self::assertSame(
            [
                ModuleScript::class . ':/build/assets/bundle.js',
                ModulePreload::class . ':/build/assets/vendor.js',
            ],
            $this->describe($assets),
            'A shared output file must appear once without suppressing later preloads.',
        );
    }

    public function testImportedCssChunkIsRenderedAndNotPreloaded(): void
    {
        $assets = $this->vite('css-chunk-import-manifest.json', ['resources/js/app.js'])->resolve();

        self::assertSame(
            [
                Stylesheet::class . ':/build/assets/shared-styles.css',
                ModuleScript::class . ':/build/assets/app-abc123.js',
                ModulePreload::class . ':/build/assets/after-css.js',
            ],
            $this->describe($assets),
            'Imported CSS must be emitted as a stylesheet and excluded from preloads.',
        );
    }

    public function testImportsUseDependencyFirstPostOrder(): void
    {
        $assets = $this->vite('deep-import-manifest.json', ['resources/js/app.js'])->resolve();

        self::assertSame(
            [
                ModuleScript::class . ':/build/assets/app-deep.js',
                ModulePreload::class . ':/build/assets/chunk-c.js',
                ModulePreload::class . ':/build/assets/chunk-b.js',
                ModulePreload::class . ':/build/assets/chunk-a.js',
            ],
            $this->describe($assets),
            'Preloads must follow dependency-first post-order.',
        );
    }

    public function testLeadingSlashAssetPathsAreNormalized(): void
    {
        $assets = $this->vite('leading-slash-asset-manifest.json', ['resources/js/app.js'])->resolve();

        self::assertSame(
            [
                Stylesheet::class . ':/build/assets/leading-slash.css',
                ModuleScript::class . ':/build/assets/leading-slash.js',
            ],
            $this->describe($assets),
            'Leading slashes must not create double separators.',
        );
    }

    public function testModulePreloadCanBeDisabled(): void
    {
        $configuration = new ProductionConfiguration(
            manifestPath: __DIR__ . '/Fixture/manifest.json',
            assetBaseUrl: '/build',
            modulePreload: false,
        );

        $assets = (new Vite($configuration, ['views/foo.js']))->resolve();

        self::assertSame(
            [],
            $assets->modulePreloads(),
            'No preload assets must be emitted.',
        );
        self::assertCount(
            1,
            $assets->moduleScripts(),
            'The entrypoint script must remain available.',
        );
    }

    public function testMultipleEntrypointsDeduplicateSharedAssets(): void
    {
        $assets = $this->vite(
            'multi-entry-manifest.json',
            ['resources/js/app.js', 'resources/js/admin.js'],
        )->resolve();

        self::assertSame(
            [
                Stylesheet::class . ':/build/assets/app-abc123.css',
                Stylesheet::class . ':/build/assets/shared-abc123.css',
                Stylesheet::class . ':/build/assets/admin-def456.css',
                ModuleScript::class . ':/build/assets/app-abc123.js',
                ModuleScript::class . ':/build/assets/admin-def456.js',
                ModulePreload::class . ':/build/assets/shared-abc123.js',
                ModulePreload::class . ':/build/assets/utils-def456.js',
            ],
            $this->describe($assets),
            'Shared stylesheets and preloads must appear once in stable order.',
        );
    }

    public function testProductionResolvesOfficialManifestInDocumentedOrder(): void
    {
        $assets = $this->vite('manifest.json', ['views/foo.js'])->resolve();

        self::assertSame(
            [
                Stylesheet::class . ':/build/assets/foo-5UjPuW-k.css',
                Stylesheet::class . ':/build/assets/shared-ChJ_j-JJ.css',
                ModuleScript::class . ':/build/assets/foo-BRBmoGS9.js',
                ModulePreload::class . ':/build/assets/shared-B7PI925R.js',
            ],
            $this->describe($assets),
            'Stylesheets must precede scripts and dependency preloads.',
        );
    }

    public function testResolveAcceptsStringOverride(): void
    {
        $vite = $this->vite('manifest.json', []);

        $assets = $vite->resolve('/views/foo.js');

        self::assertCount(
            1,
            $assets->moduleScripts(),
            'The string override must select one entrypoint script.',
        );
    }

    public function testRootBaseUrlProducesRootRelativeAssetUrls(): void
    {
        $configuration = new ProductionConfiguration(
            manifestPath: __DIR__ . '/Fixture/css-entrypoint-manifest.json',
            assetBaseUrl: '/',
        );
        $stylesheets = (new Vite($configuration, ['resources/css/app.css']))->resolve()->stylesheets();

        self::assertSame(
            ['/assets/app-abc123.css'],
            array_map(static fn(Stylesheet $stylesheet): string => $stylesheet->url, $stylesheets),
            'The root base must produce a root-relative asset URL.',
        );
    }

    public function testThrowEntrypointNotFoundExceptionWhenEntrypointIsMissing(): void
    {
        $vite = $this->vite('manifest.json', ['views/missing.js']);

        $this->expectException(EntrypointNotFoundException::class);
        $this->expectExceptionMessage(
            Message::ENTRYPOINT_NOT_FOUND->getMessage(
                __DIR__ . '/Fixture/manifest.json',
                'views/missing.js',
            ),
        );

        $vite->resolve();
    }

    public function testThrowInvalidManifestExceptionWhenManifestKeyIsNotAnEntrypoint(): void
    {
        $vite = $this->vite('non-entry-manifest.json', ['_chunk.js']);

        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage(
            Message::MANIFEST_ENTRY_NOT_ENTRYPOINT->getMessage(
                '_chunk.js',
                __DIR__ . '/Fixture/non-entry-manifest.json',
            ),
        );

        $vite->resolve();
    }

    /**
     * Flattens a collection into `type:url` strings so an assertion can compare both order and asset type at once.
     *
     * @param AssetCollection $collection Resolved assets to describe.
     *
     * @return list<string> One `type:url` entry per URL-bearing asset, in collection order.
     */
    private function describe(AssetCollection $collection): array
    {
        $description = [];

        foreach ($collection as $asset) {
            if ($asset instanceof ModuleScript || $asset instanceof ModulePreload || $asset instanceof Stylesheet) {
                $description[] = $asset::class . ':' . $asset->url;
            }
        }

        return $description;
    }

    /**
     * Builds a facade over a manifest fixture, using `/build` as the asset base URL.
     *
     * @param string $fixture File name of the manifest fixture under `tests/Fixture`.
     * @param list<string> $entrypoints Default entrypoints for the facade.
     *
     * @return Vite Facade configured for production resolution.
     */
    private function vite(string $fixture, array $entrypoints): Vite
    {
        return new Vite(
            new ProductionConfiguration(
                manifestPath: __DIR__ . '/Fixture/' . $fixture,
                assetBaseUrl: '/build',
            ),
            entrypoints: $entrypoints,
        );
    }
}
