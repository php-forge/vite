<?php

declare(strict_types=1);

namespace PHPForge\Vite\Html;

use PHPForge\Vite\Asset\{AssetCollection, AssetInterface, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\HtmlRenderingException;
use UIAwesome\Html\Metadata\{Link, Script};

use function implode;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * Safely renders neutral Vite assets as HTML5 tags through UI Awesome.
 */
final class HtmlRenderer
{
    /**
     * @var array<string, true>
     */
    private const array RESERVED_ATTRIBUTES = [
        'href' => true,
        'nonce' => true,
        'rel' => true,
        'src' => true,
        'type' => true,
    ];

    public function render(AssetCollection $assets, HtmlRenderOptions|null $options = null): string
    {
        $options ??= new HtmlRenderOptions();

        $tags = [];

        foreach ($assets as $asset) {
            $tags[] = $this->renderAsset($asset, $options);
        }

        return implode($options->separator, $tags);
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function attributesFor(AssetInterface $asset, HtmlRenderOptions $options): array
    {
        $attributes = [];

        if ($options->nonce !== null) {
            $attributes['nonce'] = $options->nonce;
        }

        $seen = [];

        foreach ($options->attributesFor($asset) as $name => $value) {
            [$name, $value, $normalizedName] = $this->validateCustomAttribute($name, $value, $seen);

            $seen[$normalizedName] = true;

            if ($value === null || $value === false) {
                continue;
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    private function renderAsset(AssetInterface $asset, HtmlRenderOptions $options): string
    {
        if ($asset instanceof ModuleScript) {
            return Script::tag()
                ->attributes($this->attributesFor($asset, $options))
                ->type('module')
                ->src($asset->url)
                ->render();
        }

        if ($asset instanceof Stylesheet) {
            return Link::tag()
                ->attributes($this->attributesFor($asset, $options))
                ->rel('stylesheet')
                ->href($asset->url)
                ->render();
        }

        if ($asset instanceof ModulePreload) {
            return Link::tag()
                ->attributes($this->attributesFor($asset, $options))
                ->rel('modulepreload')
                ->href($asset->url)
                ->render();
        }

        if ($asset instanceof InlineModule) {
            $source = preg_replace('~</script~i', '<\\/script', $asset->source);

            if ($source === null) {
                throw new HtmlRenderingException('Unable to render the inline module source.');
            }

            return Script::tag()
                ->attributes($this->attributesFor($asset, $options))
                ->type('module')
                ->html($source)
                ->render();
        }

        throw new HtmlRenderingException('Unsupported AssetInterface implementation.');
    }

    /**
     * @param array<string, true> $seen
     *
     * @return array{string, bool|float|int|string|null, string}
     */
    private function validateCustomAttribute(mixed $name, mixed $value, array $seen): array
    {
        if (!is_string($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $name) !== 1) {
            throw new HtmlRenderingException(
                'HTML attribute names must use a safe HTML name syntax.',
            );
        }

        $normalizedName = strtolower($name);

        if (
            isset(self::RESERVED_ATTRIBUTES[$normalizedName])
            || isset($seen[$normalizedName])
            || str_starts_with($normalizedName, 'on')
            || $normalizedName === 'style'
        ) {
            throw new HtmlRenderingException(
                sprintf('The HTML attribute "%s" is reserved or unsafe.', $name),
            );
        }

        if (
            (
                !is_string($value)
                && !is_int($value)
                && !is_float($value)
                && !is_bool($value)
                && $value !== null
            )
            || (is_float($value) && !is_finite($value))
        ) {
            throw new HtmlRenderingException(
                sprintf('The HTML attribute "%s" has an unsupported value.', $name),
            );
        }

        return [$name, $value, $normalizedName];
    }
}
