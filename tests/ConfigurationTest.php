<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{InlineModule, ModuleScript};
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Exception\{ConfigurationException, InvalidEntrypointException};
use PHPForge\Vite\Tests\Fixtures\CapturingInlineModuleProviderStub;
use PHPForge\Vite\Tests\Provider\ConfigurationProvider;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Unit tests for {@see DevelopmentConfiguration} and {@see ProductionConfiguration} input normalization and validation.
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

    public function testDevelopmentConfigurationNormalizesValues(): void
    {
        $firstProvider = new CapturingInlineModuleProviderStub();
        $secondProvider = new CapturingInlineModuleProviderStub();
        $configuration = new DevelopmentConfiguration(
            devServerUrl: ' HTTPS://localhost:5173/vite/ ',
            entrypoints: ['/resources/js/app.js', 'resources/js/app.js'],
            inlineModuleProviders: [$firstProvider, $secondProvider],
        );

        self::assertSame(
            'HTTPS://localhost:5173/vite',
            $configuration->devServerUrl,
            'Outer whitespace and the trailing slash must be removed.',
        );
        self::assertSame(
            ['resources/js/app.js'],
            $configuration->entrypoints,
            'Leading slashes and duplicates must be normalized.',
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

    public function testProductionConfigurationNormalizesBaseUrl(): void
    {
        $configuration = new ProductionConfiguration(
            manifestPath: __DIR__ . '/Fixture/manifest.json',
            assetBaseUrl: ' HTTPS://cdn.example.com/build/ ',
            entrypoints: ['views/foo.js'],
        );

        self::assertSame(
            'HTTPS://cdn.example.com/build',
            $configuration->assetBaseUrl,
            'Outer whitespace and the trailing slash must be removed.',
        );
        self::assertSame(
            ['views/foo.js'],
            $configuration->entrypoints,
            'Entrypoints must retain their source paths.',
        );
        self::assertTrue(
            $configuration->modulePreload,
            'Module preloading must be enabled by default.',
        );
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'invalidDevelopmentServerUrls')]
    public function testThrowConfigurationExceptionForInvalidDevelopmentServerUrl(string $url): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'devServerUrl',
        );

        new DevelopmentConfiguration($url);
    }

    public function testThrowConfigurationExceptionForInvalidInlineModuleProvider(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'InlineModuleProviderInterface',
        );

        (new ReflectionClass(DevelopmentConfiguration::class))->newInstanceArgs(
            ['http://localhost:5173', [], true, [new stdClass()]],
        );
    }

    public function testThrowConfigurationExceptionForNonAbsoluteManifestPath(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'absolute filesystem path',
        );

        new ProductionConfiguration('@webroot/build/.vite/manifest.json', '/build');
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'unsafeAssetUrls')]
    public function testThrowConfigurationExceptionForUnsafeAssetUrl(string $url, string $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new ModuleScript($url);
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'invalidProductionBaseUrls')]
    public function testThrowConfigurationExceptionForUnsafeProductionBaseUrl(string $url, string $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new ProductionConfiguration(__DIR__ . '/Fixture/manifest.json', $url);
    }

    public function testThrowConfigurationExceptionForWhitespaceInlineModuleSource(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'must not be empty',
        );

        new InlineModule('   ');
    }

    #[DataProviderExternal(ConfigurationProvider::class, 'invalidEntrypoints')]
    public function testThrowInvalidEntrypointExceptionForInvalidRelativeSourcePath(string $entrypoint): void
    {
        $this->expectException(InvalidEntrypointException::class);
        $this->expectExceptionMessage(
            'relative source path',
        );

        new DevelopmentConfiguration('http://localhost:5173', [$entrypoint]);
    }

    public function testThrowInvalidEntrypointExceptionForNonStringEntrypoint(): void
    {
        $this->expectException(InvalidEntrypointException::class);
        $this->expectExceptionMessage(
            'must be a string',
        );

        (new ReflectionClass(DevelopmentConfiguration::class))->newInstanceArgs(
            ['http://localhost:5173', [123]],
        );
    }
}
