<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\ModuleScript;
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Exception\{ConfigurationException, InvalidEntrypointException};
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
    public function testDevelopmentConfigurationNormalizesValues(): void
    {
        $configuration = new DevelopmentConfiguration(
            devServerUrl: 'https://localhost:5173/vite/',
            entrypoints: ['/resources/js/app.js', 'resources/js/app.js'],
        );

        self::assertSame(
            'https://localhost:5173/vite',
            $configuration->devServerUrl,
            'The trailing slash must be removed.',
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
    }

    public function testProductionConfigurationNormalizesBaseUrl(): void
    {
        $configuration = new ProductionConfiguration(
            manifestPath: __DIR__ . '/Fixture/manifest.json',
            assetBaseUrl: 'https://cdn.example.com/build/',
            entrypoints: ['views/foo.js'],
        );

        self::assertSame(
            'https://cdn.example.com/build',
            $configuration->assetBaseUrl,
            'The trailing slash must be removed.',
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
    public function testThrowConfigurationExceptionForUnsafeAbsoluteAssetUrl(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'HTTP(S)',
        );

        new ModuleScript('data:text/javascript,alert(1)');
    }

    public function testThrowConfigurationExceptionForUnsafeProductionBaseUrl(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'HTTP(S)',
        );

        new ProductionConfiguration(__DIR__ . '/Fixture/manifest.json', 'javascript:alert(1)');
    }

    public function testThrowInvalidEntrypointExceptionForInvalidRelativeSourcePath(): void
    {
        $this->expectException(InvalidEntrypointException::class);
        $this->expectExceptionMessage(
            'relative source path',
        );

        new DevelopmentConfiguration('http://localhost:5173', ['  ']);
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
