<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{AssetCollection, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\ConfigurationException;
use PHPForge\Vite\Tests\Fixtures\UnsupportedAssetStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * Unit tests for {@see AssetCollection} composition, filtering, iteration, and validation.
 */
#[Group('asset')]
final class AssetCollectionTest extends TestCase
{
    public function testCollectionCanBeComposedWithoutMutation(): void
    {
        $script = new ModuleScript('/build/app.js');
        $stylesheet = new Stylesheet('/build/app.css');
        $collection = new AssetCollection([$script]);

        $prepended = $collection->prepend($stylesheet, $script);
        $appended = $collection->append($script, $stylesheet);

        self::assertSame(
            [$script],
            $collection->all(),
            'The source collection must remain unchanged.',
        );
        self::assertSame(
            [$stylesheet, $script],
            $prepended->all(),
            'Prepended assets must appear first.',
        );
        self::assertSame(
            [$script, $stylesheet],
            $appended->all(),
            'Appended assets must appear last.',
        );
    }

    public function testCollectionDeduplicatesAssetsAndPreservesInsertionOrder(): void
    {
        $script = new ModuleScript('/build/app.js');
        $stylesheet = new Stylesheet('/build/app.css');
        $preload = new ModulePreload('/build/vendor.js');
        $inline = new InlineModule('window.ready = true');
        $collection = new AssetCollection([$stylesheet, $script, $stylesheet, $preload, $inline, $inline]);

        self::assertSame(
            [$stylesheet, $script, $preload, $inline],
            $collection->all(),
            'First occurrences must define the asset order.',
        );
        self::assertSame(
            [$script],
            $collection->moduleScripts(),
            'Only module scripts must be returned.',
        );
        self::assertSame(
            [$preload],
            $collection->modulePreloads(),
            'Only module preloads must be returned.',
        );
        self::assertSame(
            [$inline],
            $collection->inlineModules(),
            'Only inline modules must be returned.',
        );
        self::assertCount(
            4,
            $collection,
            'Count must reflect unique assets.',
        );
        self::assertSame(
            $collection->all(),
            iterator_to_array($collection),
            'Iteration must preserve collection order.',
        );
    }

    public function testThrowConfigurationExceptionForUnsupportedAssetImplementation(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Unsupported',
        );

        new AssetCollection([new UnsupportedAssetStub()]);
    }
}
