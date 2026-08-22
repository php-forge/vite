<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{AssetCollection, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\{ConfigurationException, Message};
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
        $appended = $collection->append($stylesheet);

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
        $stylesheet = new Stylesheet('/build/shared.js');
        $script = new ModuleScript('/build/shared.js');
        $preload = new ModulePreload('/build/shared.js');
        $inline = new InlineModule('window.first = true;');
        $secondStylesheet = new Stylesheet('/build/second.css');
        $secondScript = new ModuleScript('/build/second.js');
        $secondPreload = new ModulePreload('/build/second-preload.js');
        $secondInline = new InlineModule('window.second = true;');
        $collection = new AssetCollection(
            [
                $stylesheet,
                $script,
                $preload,
                $inline,
                new Stylesheet('/build/shared.js'),
                new ModuleScript('/build/shared.js'),
                new ModulePreload('/build/shared.js'),
                new InlineModule('window.first = true;'),
                $secondStylesheet,
                $secondScript,
                $secondPreload,
                $secondInline,
            ],
        );

        self::assertSame(
            [
                $stylesheet,
                $script,
                $preload,
                $inline,
                $secondStylesheet,
                $secondScript,
                $secondPreload,
                $secondInline,
            ],
            $collection->all(),
            'First occurrences must define the asset order.',
        );
        self::assertSame(
            [$script, $secondScript],
            $collection->moduleScripts(),
            'Only module scripts must be returned.',
        );
        self::assertSame(
            [$stylesheet, $secondStylesheet],
            $collection->stylesheets(),
            'Only stylesheets must be returned.',
        );
        self::assertSame(
            [$preload, $secondPreload],
            $collection->modulePreloads(),
            'Only module preloads must be returned.',
        );
        self::assertSame(
            [$inline, $secondInline],
            $collection->inlineModules(),
            'Only inline modules must be returned.',
        );
        self::assertCount(
            8,
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
            Message::ASSET_IMPLEMENTATION_UNSUPPORTED->getMessage(),
        );

        new AssetCollection([new UnsupportedAssetStub()]);
    }
}
