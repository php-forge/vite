<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{InlineModule, ModuleScript};
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Exception\{ConfigurationException, InvalidEntrypointException, Message};
use PHPForge\Vite\Manifest\ManifestLoader;
use PHPForge\Vite\Tests\Fixture\CapturingInlineModuleProviderStub;
use PHPForge\Vite\Tests\Provider\ConfigurationProvider;
use PHPForge\Vite\Vite;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for configuration validation and {@see Vite} facade construction.
 *
 * {@see ConfigurationProvider} for test case data providers.
 */
#[Group('configuration')]
final class ConfigurationTest extends TestCase
{
    public function testAssetValueAcceptsCaseInsensitiveHttpScheme(): void
    {
        self::assertSame(
            'HTTPS://cdn.example.com/app.js',
            (new ModuleScript(' HTTPS://cdn.example.com/app.js '))->url,
            'Outer whitespace must be removed from the safe absolute URL.',
        );
    }

    public function testConfigurationFactoriesCreateConfiguredInstances(): void
    {
        $manifestPath = __DIR__ . '/Fixture/manifest.json';

        $provider = new CapturingInlineModuleProviderStub();

        $development = DevelopmentConfiguration::create(
            'http://localhost:5173',
            false,
            [$provider],
        );
        $production = ProductionConfiguration::create(
            $manifestPath,
            '/build',
            false,
        );

        self::assertSame(
            'http://localhost:5173',
            $development->devServerUrl,
            'The development factory must preserve the normalized server URL.',
        );
        self::assertFalse(
            $development->includeViteClient,
            'The development factory must preserve the client setting.',
        );
        self::assertSame(
            [$provider],
            $development->inlineModuleProviders,
            'The development factory must preserve inline module providers.',
        );
        self::assertSame(
            $manifestPath,
            $production->manifestPath,
            'The production factory must preserve the manifest path.',
        );
        self::assertFalse(
            $production->modulePreload,
            'The production factory must preserve the preload setting.',
        );
    }

    public function testCreateReturnsConfiguredViteFacade(): void
    {
        $manifestPath = __DIR__ . '/Fixture/manifest.json';

        $loader = new ManifestLoader();

        $cachedManifest = $loader->load($manifestPath);

        $vite = Vite::create(
            ProductionConfiguration::create($manifestPath, '/build'),
            ['views/foo.js'],
            $loader,
        );

        self::assertSame(
            ['/build/assets/foo-BRBmoGS9.js'],
            array_map(
                static fn(ModuleScript $script): string => $script->url,
                $vite->resolve()->moduleScripts(),
            ),
            'The factory must forward the configuration and default entrypoints.',
        );

        $vite->clearManifestCache();

        self::assertNotSame(
            $cachedManifest,
            $loader->load($manifestPath),
            'The factory must forward a shared manifest loader.',
        );
    }

    public function testDevelopmentConfigurationEnablesViteClientByDefault(): void
    {
        $configuration = new DevelopmentConfiguration('http://localhost:5173');

        self::assertTrue(
            $configuration->includeViteClient,
            'The development configuration must enable the Vite client by default.',
        );
    }

    public function testDevelopmentConfigurationNormalizesValues(): void
    {
        $firstProvider = new CapturingInlineModuleProviderStub();
        $secondProvider = new CapturingInlineModuleProviderStub();

        $configuration = DevelopmentConfiguration::create(
            devServerUrl: ' HTTPS://localhost:5173/vite/ ',
            inlineModuleProviders: [$firstProvider, $secondProvider],
        );

        self::assertSame(
            'HTTPS://localhost:5173/vite',
            $configuration->devServerUrl,
            'Outer whitespace and the trailing slash must be removed.',
        );
        self::assertTrue(
            $configuration->includeViteClient,
            'The Vite client must be enabled by default.',
        );
        self::assertSame(
            [$firstProvider, $secondProvider],
            $configuration->inlineModuleProviders,
            'Every configured inline module provider must be retained.',
        );
    }

    public function testProductionConfigurationEnablesModulePreloadByDefault(): void
    {
        $configuration = new ProductionConfiguration(__DIR__ . '/Fixture/manifest.json', '/build');

        self::assertTrue(
            $configuration->modulePreload,
            'The production configuration must enable module preloading by default.',
        );
    }

    public function testProductionConfigurationNormalizesBaseUrl(): void
    {
        $configuration = ProductionConfiguration::create(
            manifestPath: __DIR__ . '/Fixture/manifest.json',
            assetBaseUrl: ' HTTPS://cdn.example.com/build/ ',
        );

        self::assertSame(
            'HTTPS://cdn.example.com/build',
            $configuration->assetBaseUrl,
            'Outer whitespace and the trailing slash must be removed.',
        );
        self::assertTrue(
            $configuration->modulePreload,
            'Module preloading must be enabled by default.',
        );
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'invalidDevelopmentServerUrls')]
    public function testThrowConfigurationExceptionForInvalidDevelopmentServerUrl(string $url, Message $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            $message->getMessage(),
        );

        DevelopmentConfiguration::create($url);
    }

    public function testThrowConfigurationExceptionForInvalidInlineModuleProvider(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            Message::DEVELOPMENT_INLINE_MODULE_PROVIDER_INVALID->getMessage(),
        );

        DevelopmentConfiguration::create('http://localhost:5173', inlineModuleProviders: [new stdClass()]);
    }

    public function testThrowConfigurationExceptionForNonAbsoluteManifestPath(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            Message::FILESYSTEM_PATH_INVALID->getMessage('manifestPath'),
        );

        ProductionConfiguration::create('@webroot/build/.vite/manifest.json', '/build');
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'unsafeAssetUrls')]
    public function testThrowConfigurationExceptionForUnsafeAssetUrl(string $url, Message $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            $message->getMessage(),
        );

        new ModuleScript($url);
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'invalidProductionBaseUrls')]
    public function testThrowConfigurationExceptionForUnsafeProductionBaseUrl(string $url, Message $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            $message->getMessage(),
        );

        ProductionConfiguration::create(__DIR__ . '/Fixture/manifest.json', $url);
    }

    public function testThrowConfigurationExceptionForWhitespaceInlineModuleSource(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            Message::INLINE_MODULE_SOURCE_EMPTY->getMessage(),
        );

        new InlineModule('   ');
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'invalidEntrypoints')]
    public function testThrowInvalidEntrypointExceptionForInvalidRelativeSourcePath(
        string $entrypoint,
        Message $message,
    ): void {
        $this->expectException(InvalidEntrypointException::class);
        $this->expectExceptionMessage(
            $message->getMessage(),
        );

        Vite::create(DevelopmentConfiguration::create('http://localhost:5173'), [$entrypoint]);
    }

    public function testThrowInvalidEntrypointExceptionForNonStringEntrypoint(): void
    {
        $this->expectException(InvalidEntrypointException::class);
        $this->expectExceptionMessage(
            Message::ENTRYPOINT_TYPE_INVALID->getMessage(),
        );

        Vite::create(DevelopmentConfiguration::create('http://localhost:5173'), [123]);
    }
}
