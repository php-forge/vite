<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use PHPForge\Vite\Exception\{ConfigurationException, Message};
use Traversable;

use function count;
use function in_array;

/**
 * Immutable, insertion-ordered collection of deduplicated neutral assets.
 *
 * @implements IteratorAggregate<int, InlineModule|ModulePreload|ModuleScript|Stylesheet>
 */
final readonly class AssetCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<InlineModule|ModulePreload|ModuleScript|Stylesheet> Accepted assets in insertion order, with per-type
     * duplicates removed.
     */
    private array $assets;

    /**
     * @param iterable<mixed> $assets Assets to validate and collect.
     *
     * @throws ConfigurationException if a value does not implement {@see AssetInterface}, or implements it without
     * being one of the four supported asset types.
     */
    public function __construct(iterable $assets = [])
    {
        $normalized = [];
        $seen = [];

        foreach ($assets as $asset) {
            $asset = $this->requireAsset($asset);

            $type = $asset::class;

            $value = $asset instanceof InlineModule ? $asset->source : $asset->url;

            if (in_array($value, $seen[$type] ?? [], true)) {
                continue;
            }

            $seen[$type][] = $value;
            $normalized[] = $asset;
        }

        $this->assets = $normalized;
    }

    /**
     * Returns every collected asset in insertion order, regardless of type.
     *
     * @return list<InlineModule|ModulePreload|ModuleScript|Stylesheet> The collected assets.
     */
    public function all(): array
    {
        return $this->assets;
    }

    /**
     * Returns a new collection with the supplied assets added after the current ones.
     *
     * The receiver is left untouched; deduplication is re-applied across the combined sequence.
     *
     * @param AssetInterface ...$assets Assets to add at the end.
     *
     * @throws ConfigurationException if an asset is not one of the four supported types.
     *
     * @return AssetCollection A new collection holding both sequences.
     */
    public function append(AssetInterface ...$assets): AssetCollection
    {
        return new self([...$this->assets, ...$assets]);
    }

    /**
     * Counts the collected assets after deduplication.
     *
     * @return int<0, max> Number of assets in the collection.
     */
    public function count(): int
    {
        return count($this->assets);
    }

    /**
     * Iterates over the collected assets in insertion order.
     *
     * @return Traversable<int, InlineModule|ModulePreload|ModuleScript|Stylesheet> Iterator over the collected assets.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->assets);
    }

    /**
     * Filters the collection down to its inline modules.
     *
     * @return list<InlineModule> Inline modules in insertion order.
     */
    public function inlineModules(): array
    {
        $assets = [];

        foreach ($this->assets as $asset) {
            if ($asset instanceof InlineModule) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Filters the collection down to its module-preload hints.
     *
     * @return list<ModulePreload> Module preloads in insertion order.
     */
    public function modulePreloads(): array
    {
        $assets = [];

        foreach ($this->assets as $asset) {
            if ($asset instanceof ModulePreload) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Filters the collection down to its module scripts.
     *
     * @return list<ModuleScript> Module scripts in insertion order.
     */
    public function moduleScripts(): array
    {
        $assets = [];

        foreach ($this->assets as $asset) {
            if ($asset instanceof ModuleScript) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Returns a new collection with the supplied assets added before the current ones.
     *
     * The receiver is left untouched; deduplication is re-applied across the combined sequence.
     *
     * @param AssetInterface ...$assets Assets to add at the beginning.
     *
     * @throws ConfigurationException if an asset is not one of the four supported types.
     *
     * @return AssetCollection A new collection holding both sequences.
     */
    public function prepend(AssetInterface ...$assets): AssetCollection
    {
        return new self([...$assets, ...$this->assets]);
    }

    /**
     * Filters the collection down to its stylesheets.
     *
     * @return list<Stylesheet> Stylesheets in insertion order.
     */
    public function stylesheets(): array
    {
        $assets = [];

        foreach ($this->assets as $asset) {
            if ($asset instanceof Stylesheet) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Narrows an arbitrary value to one of the four supported asset types.
     *
     * @param mixed $asset Value taken from the incoming iterable.
     *
     * @throws ConfigurationException if the value does not implement {@see AssetInterface}, or implements it without
     * being a supported type.
     *
     * @return InlineModule|ModulePreload|ModuleScript|Stylesheet The narrowed asset.
     */
    private function requireAsset(mixed $asset): InlineModule|ModulePreload|ModuleScript|Stylesheet
    {
        if (!$asset instanceof AssetInterface) {
            throw new ConfigurationException(
                Message::ASSET_COLLECTION_ITEM_INVALID->getMessage(),
            );
        }

        if (
            !$asset instanceof InlineModule
            && !$asset instanceof ModulePreload
            && !$asset instanceof ModuleScript
            && !$asset instanceof Stylesheet
        ) {
            throw new ConfigurationException(
                Message::ASSET_IMPLEMENTATION_UNSUPPORTED->getMessage(),
            );
        }

        return $asset;
    }
}
