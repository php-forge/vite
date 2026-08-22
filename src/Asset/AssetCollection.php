<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use PHPForge\Vite\Exception\ConfigurationException;
use Traversable;

use function count;
use function in_array;

/**
 * Immutable, insertion-ordered collection of deduplicated neutral assets.
 *
 * @implements IteratorAggregate<int, AssetInterface>
 */
final readonly class AssetCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<AssetInterface>
     */
    private array $assets;

    /**
     * @param iterable<AssetInterface> $assets
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
     * @return list<AssetInterface>
     */
    public function all(): array
    {
        return $this->assets;
    }

    public function append(AssetInterface ...$assets): self
    {
        return new self([...$this->assets, ...$assets]);
    }

    public function count(): int
    {
        return count($this->assets);
    }

    /**
     * @return Traversable<int, AssetInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->assets);
    }

    /**
     * @return list<InlineModule>
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
     * @return list<ModulePreload>
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
     * @return list<ModuleScript>
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

    public function prepend(AssetInterface ...$assets): self
    {
        return new self([...$assets, ...$this->assets]);
    }

    /**
     * @return list<Stylesheet>
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
     * @return InlineModule|ModulePreload|ModuleScript|Stylesheet
     */
    private function requireAsset(mixed $asset): AssetInterface
    {
        if (!$asset instanceof AssetInterface) {
            throw new ConfigurationException(
                'AssetCollection accepts only AssetInterface instances.',
            );
        }

        if (
            !$asset instanceof InlineModule
            && !$asset instanceof ModulePreload
            && !$asset instanceof ModuleScript
            && !$asset instanceof Stylesheet
        ) {
            throw new ConfigurationException(
                'Unsupported AssetInterface implementation.',
            );
        }

        return $asset;
    }
}
