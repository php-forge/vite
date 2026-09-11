<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Debug;

use PHPForge\Vite\Asset\AssetCollection;
use PHPForge\Vite\Configuration\{DevelopmentConfiguration, ProductionConfiguration};
use PHPForge\Vite\Debug\{ViteCollector, VitePanel};
use PHPForge\Vite\Development\InlineModuleProviderInterface;
use PHPForge\Vite\Event\AssetsResolved;
use PHPForge\Vite\Exception\ManifestNotFoundException;
use PHPForge\Vite\Tests\Fixture\{CollectingEventDispatcherStub, CountingInlineModuleProviderStub};
use PHPForge\Vite\Tests\Provider\ViteCollectorProvider;
use PHPForge\Vite\Vite;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use stdClass;

use function count;

/**
 * Unit tests for {@see ViteCollector} resolution observation, request lifecycle, and captured payloads.
 *
 * {@see ViteCollectorProvider} for test case data providers.
 */
final class ViteCollectorTest extends TestCase
{
    public function testCaptureIsNullOutsideTheCycleAndEmptyInsideIt(): void
    {
        $collector = new ViteCollector();

        $vite = self::developmentVite(
            self::developmentConfiguration(),
            new CollectingEventDispatcherStub([$collector]),
        );

        $vite->resolve();

        self::assertNull(
            $collector->capture(),
            "No cycle means 'null'.",
        );

        $collector->startup();

        self::assertSame(
            ['components' => []],
            $collector->capture(),
            'Observed empty capture, not missing.',
        );
        self::assertFalse(
            (new VitePanel())->present(['components' => []])->isActive(),
            'An empty capture must not activate the panel.',
        );

        $collector->shutdown();
    }

    /**
     * @param list<list<string>|string> $resolutions
     * @param array<string, mixed> $expected
     */
    #[DataProviderExternal(ViteCollectorProvider::class, 'developmentCaptures')]
    public function testDevelopmentCaptureDescribesEveryResolutionInTheCycle(array $resolutions, array $expected): void
    {
        $collector = new ViteCollector();
        $dispatcher = new CollectingEventDispatcherStub([$collector]);

        $vite = self::developmentVite(
            self::developmentConfiguration(),
            $dispatcher,
        );

        $collector->startup();

        foreach ($resolutions as $entrypoints) {
            $vite->resolve($entrypoints);
        }

        $capture = $collector->capture();

        self::assertCount(
            count($resolutions),
            $dispatcher->events,
            'One event per resolution.',
        );
        self::assertNotNull(
            $capture,
            'An active cycle must produce a capture.',
        );
        self::assertSame(
            $expected,
            $capture,
            'Components must accumulate in resolution order.',
        );
        self::assertTrue(
            (new VitePanel())->present($capture)->isActive(),
            'Captured components must activate the panel.',
        );

        $collector->shutdown();
    }

    public function testDispatchedEventCarriesTheResolvedAssetsAndConfiguration(): void
    {
        $collector = new ViteCollector();

        $configuration = self::developmentConfiguration();

        $dispatcher = new CollectingEventDispatcherStub([$collector]);

        $vite = self::developmentVite(
            $configuration,
            $dispatcher,
        );

        $collector->startup();

        $assets = $vite->resolve([' /app.js ', 'app.js']);

        $event = $dispatcher->events[0] ?? null;

        self::assertInstanceOf(
            AssetsResolved::class,
            $event,
            'Every resolution must be dispatched.',
        );
        self::assertSame(
            $assets,
            $event->assets,
            'Assets must reach the listener unchanged.',
        );
        self::assertSame(
            $configuration,
            $event->configuration,
            'Configuration must reach the listener unchanged.',
        );
        self::assertNull(
            $event->manifest,
            'A development resolution has no manifest.',
        );
        self::assertSame(
            ['app.js'],
            $event->entrypoints,
            'Entrypoints must be trimmed and deduplicated.',
        );

        $collector->shutdown();
    }

    public function testDispatchForwardsResolutionsAndIgnoresOtherEvents(): void
    {
        $unrelated = new stdClass();
        $idle = new ViteCollector();

        $idle->startup();

        self::assertSame(
            $unrelated,
            $idle->dispatch($unrelated),
            'An unrelated event must pass through.',
        );
        self::assertSame(
            ['components' => []],
            $idle->capture(),
            'An unrelated event must not be captured.',
        );

        $idle->shutdown();

        $collector = new ViteCollector();

        $vite = self::developmentVite(
            self::developmentConfiguration(),
            $collector,
        );

        $collector->startup();

        $vite->resolve('app.js');

        $capture = $collector->capture();

        self::assertNotNull(
            $capture,
            'An active cycle must produce a capture.',
        );

        $components = $capture['components'] ?? null;

        self::assertIsArray(
            $components,
            'The capture must carry a component list.',
        );
        self::assertCount(
            1,
            $components,
            'The collector must observe the resolution it dispatched itself.',
        );

        $collector->shutdown();
    }

    public function testExternalResolutionWithoutManifestDoesNotReadTheFilesystem(): void
    {
        $collector = new ViteCollector();

        $collector->startup();

        $collector(
            new AssetsResolved(
                ProductionConfiguration::create(
                    '/not-present/manifest.json',
                    '/build',
                ),
                ['app.js'],
                new AssetCollection(),
            ),
        );

        $payload = $collector->capture();

        self::assertNotNull(
            $payload,
            'An active cycle must produce a capture.',
        );

        $components = $payload['components'] ?? null;

        self::assertIsArray(
            $components,
            'The capture must carry a component list.',
        );

        $component = $components[0] ?? null;

        self::assertIsArray(
            $component,
            'The observed resolution must be described.',
        );
        self::assertSame(
            [],
            $component['chunks'] ?? null,
            'A missing manifest must yield no chunks.',
        );

        $collector->shutdown();
    }

    public function testIdStaysStable(): void
    {
        self::assertSame(
            'vite',
            (new ViteCollector())->id(),
            'Collector ID must stay stable.',
        );
    }

    public function testInlineProvidersRunOncePerResolution(): void
    {
        $collector = new ViteCollector();
        $inline = new CountingInlineModuleProviderStub();
        $dispatcher = new CollectingEventDispatcherStub([$collector]);

        $vite = self::developmentVite(
            self::developmentConfiguration($inline),
            $dispatcher,
        );

        $collector->startup();

        $vite->resolve();
        $vite->resolve('app.js');
        $vite->resolve('second.js');

        self::assertCount(
            3,
            $dispatcher->events,
            'One event per resolution.',
        );
        self::assertSame(
            3,
            $inline->calls,
            'Inline providers must run once per resolution.',
        );

        $collector->shutdown();
    }

    public function testListenerFailurePropagatesWithoutRepeatingTheOperation(): void
    {
        $failure = new RuntimeException(
            'listener failed',
        );
        $dispatcher = new CollectingEventDispatcherStub(
            [static fn(AssetsResolved $event): never => throw $failure],
        );

        $vite = self::developmentVite(
            self::developmentConfiguration(),
            $dispatcher,
        );

        try {
            $vite->resolve();

            self::fail(
                'A failing listener must not be swallowed.',
            );
        } catch (RuntimeException $caught) {
            self::assertSame(
                $failure,
                $caught,
                'The listener failure must stay primary.',
            );
        }

        self::assertCount(
            1,
            $dispatcher->events,
            'The failed operation must not be retried.',
        );
    }

    public function testProductionCaptureUsesTheResolvedManifestEvenAfterRemoval(): void
    {
        $path = sys_get_temp_dir() . '/observed-vite-' . uniqid() . '.json';

        file_put_contents(
            $path,
            json_encode(
                [
                    'app.js' => [
                        'file' => 'assets/app.js',
                        'isEntry' => true,
                        'css' => ['assets/app.css'],
                        'imports' => ['extra.js'],
                    ],
                    'extra.js' => ['file' => 'assets/extra.js'],
                ],
                JSON_THROW_ON_ERROR,
            ),
        );

        $collector = new ViteCollector();
        $dispatcher = new CollectingEventDispatcherStub([$collector]);

        $configuration = ProductionConfiguration::create(
            $path,
            '/build',
            false,
        );

        $vite = new Vite(
            $configuration,
            ['app.js'],
            eventDispatcher: $dispatcher,
        );

        $collector->startup();

        try {
            $assets = $vite->resolve();
        } finally {
            unlink($path);
        }

        $event = $dispatcher->events[0] ?? null;

        self::assertInstanceOf(
            AssetsResolved::class,
            $event,
            'The resolution must be dispatched.',
        );
        self::assertSame(
            $assets,
            $event->assets,
            'Assets must reach the listener unchanged.',
        );
        self::assertSame(
            $configuration,
            $event->configuration,
            'Configuration must reach the listener unchanged.',
        );
        self::assertNotNull(
            $event->manifest,
            'A production resolution must carry the manifest.',
        );
        self::assertCount(
            2,
            $event->manifest->chunks(),
            'Both manifest entries must be loaded.',
        );

        $capture = $collector->capture();

        self::assertSame(
            [
                'components' => [
                    [
                        'id' => 'vite-1',
                        'class' => Vite::class,
                        'implementation' => 'modern',
                        'inspectionAvailable' => true,
                        'mode' => 'production',
                        'entrypoints' => ['app.js'],
                        'baseUrl' => '/build',
                        'devServerUrl' => null,
                        'manifestPath' => $path,
                        'includeViteClient' => null,
                        'modulePreload' => false,
                        'chunks' => [
                            [
                                'name' => 'app.js',
                                'file' => 'assets/app.js',
                                'cssCount' => 1,
                                'imports' => 1,
                                'isEntry' => true,
                            ],
                            [
                                'name' => 'extra.js',
                                'file' => 'assets/extra.js',
                                'cssCount' => 0,
                                'imports' => 0,
                                'isEntry' => false,
                            ],
                        ],
                    ],
                ],
            ],
            $capture,
            'Capture must keep the manifest values read before removal.',
        );

        $collector->shutdown();

        self::assertTrue(
            (new VitePanel())->present($capture)->isActive(),
            'A replayed capture must activate the panel.',
        );

        $collector->startup();

        $this->expectException(ManifestNotFoundException::class);

        try {
            $vite->resolve();
        } finally {
            self::assertSame(
                ['components' => []],
                $collector->capture(),
                'A failed resolution must leave the cycle empty.',
            );
            self::assertCount(
                1,
                $dispatcher->events,
                'A failed resolution must not dispatch.',
            );

            $collector->shutdown();
        }
    }

    public function testShutdownDiscardsTheCycleAndStartupBeginsEmpty(): void
    {
        $collector = new ViteCollector();

        $vite = self::developmentVite(
            self::developmentConfiguration(),
            new CollectingEventDispatcherStub([$collector]),
        );

        $collector->startup();

        $vite->resolve('app.js');

        $collector->shutdown();
        $collector->shutdown();

        $vite->resolve();

        self::assertNull(
            $collector->capture(),
            'A stopped collector must not observe.',
        );

        $collector->startup();

        self::assertSame(
            ['components' => []],
            $collector->capture(),
            'A new cycle must start empty.',
        );

        $collector->shutdown();
    }

    /**
     * Builds the development configuration shared by the collector tests.
     *
     * @param InlineModuleProviderInterface ...$providers Inline module providers of the configuration.
     *
     * @return DevelopmentConfiguration Configuration pointing at the shared development server URL.
     */
    private static function developmentConfiguration(
        InlineModuleProviderInterface ...$providers,
    ): DevelopmentConfiguration {
        return DevelopmentConfiguration::create(
            ViteCollectorProvider::DEV_SERVER_URL,
            false,
            array_values($providers),
        );
    }

    /**
     * Builds a development Vite instance resolving through the supplied dispatcher.
     *
     * @param DevelopmentConfiguration $configuration Configuration the instance resolves with.
     * @param EventDispatcherInterface $dispatcher Dispatcher receiving every resolution.
     *
     * @return Vite Instance resolving the default entrypoint.
     */
    private static function developmentVite(
        DevelopmentConfiguration $configuration,
        EventDispatcherInterface $dispatcher,
    ): Vite {
        return Vite::create(
            $configuration,
            ['default.js'],
            eventDispatcher: $dispatcher,
        );
    }
}
