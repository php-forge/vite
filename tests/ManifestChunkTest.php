<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Manifest\ManifestChunk;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ManifestChunk} construction, immutable modifiers, and asset type detection.
 */
#[Group('manifest')]
final class ManifestChunkTest extends TestCase
{
    public function testConstructorAndFactoryApplyOptionalDefaults(): void
    {
        foreach (
            [
                new ManifestChunk('constructor.js', 'assets/constructor.js'),
                ManifestChunk::create('factory.js', 'assets/factory.js'),
            ] as $chunk
        ) {
            self::assertNull(
                $chunk->src(),
                "The source path must default to 'null'.",
            );
            self::assertSame(
                [],
                $chunk->css(),
                'The stylesheet list must default to empty.',
            );
            self::assertSame(
                [],
                $chunk->assets(),
                'The static asset list must default to empty.',
            );
            self::assertFalse(
                $chunk->isEntry(),
                "The entrypoint flag must default to 'false'.",
            );
            self::assertNull(
                $chunk->name(),
                "The chunk name must default to 'null'.",
            );
            self::assertFalse(
                $chunk->isDynamicEntry(),
                "The dynamic entrypoint flag must default to 'false'.",
            );
            self::assertSame(
                [],
                $chunk->imports(),
                'The static import list must default to empty.',
            );
            self::assertSame(
                [],
                $chunk->dynamicImports(),
                'The dynamic import list must default to empty.',
            );
        }
    }

    public function testCssDetectionIsCaseInsensitive(): void
    {
        self::assertTrue(
            (new ManifestChunk('app.css', 'assets/app.CSS'))->isCss(),
            'An uppercase CSS extension must be recognized.',
        );
    }

    public function testWithMethodsCanResetEveryOptionalValue(): void
    {
        $configured = ManifestChunk::create('app.js', 'assets/app.js')
            ->withSrc('resources/app.js')
            ->withCss(['assets/app.css'])
            ->withAssets(['assets/logo.svg'])
            ->withEntry()
            ->withName('app')
            ->withDynamicEntry()
            ->withImports(['vendor.js'])
            ->withDynamicImports(['lazy.js']);

        $reset = $configured
            ->withSrc(null)
            ->withCss([])
            ->withAssets([])
            ->withEntry(false)
            ->withName(null)
            ->withDynamicEntry(false)
            ->withImports([])
            ->withDynamicImports([]);

        self::assertNotSame(
            $configured,
            $reset,
            'Resetting values must return a distinct chunk instance.',
        );
        self::assertNull(
            $reset->src(),
            "The source path must reset to 'null'.",
        );
        self::assertSame(
            [],
            $reset->css(),
            'The stylesheet list must reset to empty.',
        );
        self::assertSame(
            [],
            $reset->assets(),
            'The static asset list must reset to empty.',
        );
        self::assertFalse(
            $reset->isEntry(),
            "The entrypoint flag must reset to 'false'.",
        );
        self::assertNull(
            $reset->name(),
            "The chunk name must reset to 'null'.",
        );
        self::assertFalse(
            $reset->isDynamicEntry(),
            "The dynamic entrypoint flag must reset to 'false'.",
        );
        self::assertSame(
            [],
            $reset->imports(),
            'The static import list must reset to empty.',
        );
        self::assertSame(
            [],
            $reset->dynamicImports(),
            'The dynamic import list must reset to empty.',
        );
        self::assertSame(
            'resources/app.js',
            $configured->src(),
            "Resetting must not mutate the configured source path.",
        );
        self::assertTrue(
            $configured->isEntry(),
            "Resetting must not mutate the configured entrypoint flag.",
        );
        self::assertTrue(
            $configured->isDynamicEntry(),
            "Resetting must not mutate the configured dynamic entrypoint flag.",
        );
    }

    public function testWithMethodsReturnConfiguredCopyWithoutMutatingOriginal(): void
    {
        $original = ManifestChunk::create('app.js', 'assets/app.js');

        $configured = $original
            ->withSrc('resources/app.js')
            ->withCss(['assets/app.css'])
            ->withAssets(['assets/logo.svg'])
            ->withEntry()
            ->withName('app')
            ->withDynamicEntry()
            ->withImports(['vendor.js'])
            ->withDynamicImports(['lazy.js']);

        self::assertNotSame(
            $original,
            $configured,
            'Configuration must return a distinct chunk instance.',
        );
        self::assertSame(
            'app.js',
            $configured->key,
            'The manifest key must be preserved.',
        );
        self::assertSame(
            'assets/app.js',
            $configured->file,
            'The emitted file must be preserved.',
        );
        self::assertSame(
            'resources/app.js',
            $configured->src(),
            'The source path must be replaced.',
        );
        self::assertSame(
            ['assets/app.css'],
            $configured->css(),
            'The stylesheet list must be replaced.',
        );
        self::assertSame(
            ['assets/logo.svg'],
            $configured->assets(),
            'The static asset list must be replaced.',
        );
        self::assertTrue(
            $configured->isEntry(),
            'The entrypoint flag must be enabled.',
        );
        self::assertSame(
            'app',
            $configured->name(),
            'The chunk name must be replaced.',
        );
        self::assertTrue(
            $configured->isDynamicEntry(),
            'The dynamic entrypoint flag must be enabled.',
        );
        self::assertSame(
            ['vendor.js'],
            $configured->imports(),
            'The static import list must be replaced.',
        );
        self::assertSame(
            ['lazy.js'],
            $configured->dynamicImports(),
            'The dynamic import list must be replaced.',
        );
        self::assertNull(
            $original->src(),
            'The original source path must remain unchanged.',
        );
        self::assertSame(
            [],
            $original->css(),
            'The original stylesheet list must remain unchanged.',
        );
        self::assertSame(
            [],
            $original->assets(),
            'The original static asset list must remain unchanged.',
        );
        self::assertFalse(
            $original->isEntry(),
            'The original entrypoint flag must remain unchanged.',
        );
        self::assertNull(
            $original->name(),
            'The original chunk name must remain unchanged.',
        );
        self::assertFalse(
            $original->isDynamicEntry(),
            'The original dynamic entrypoint flag must remain unchanged.',
        );
        self::assertSame(
            [],
            $original->imports(),
            'The original static import list must remain unchanged.',
        );
        self::assertSame(
            [],
            $original->dynamicImports(),
            'The original dynamic import list must remain unchanged.',
        );
    }
}
