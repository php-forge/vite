<?php

declare(strict_types=1);

namespace PHPForge\Vite\Html;

use PHPForge\Vite\Asset\{AssetCollection, AssetInterface, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\{HtmlRenderingException, Message};
use UIAwesome\Html\Metadata\{Link, Script};

use function implode;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_string;
use function preg_match;
use function preg_replace;
use function str_starts_with;
use function strtolower;

/**
 * Safely renders neutral Vite assets as HTML5 tags through UI Awesome.
 */
final class HtmlRenderer
{
    /**
     * Attribute names the renderer emits itself and therefore refuses to accept from callers.
     *
     * @var array<string, true> Lowercased attribute names used as a lookup set.
     */
    private const array RESERVED_ATTRIBUTES = [
        'href' => true,
        'nonce' => true,
        'rel' => true,
        'src' => true,
        'type' => true,
    ];

    /**
     * Renders a collection of neutral assets as HTML5 tags joined by the configured separator.
     *
     * Assets are rendered in collection order, each one mapped to the tag its type implies. The UI Awesome tag
     * builders encode URLs and attribute values; inline module source is embedded raw through `Script::html()` and is
     * protected only by neutralizing its closing `</script` sequences.
     *
     * @param AssetCollection $assets Resolved assets to render.
     * @param HtmlRenderOptions|null $options Per-render policy, or `null` to apply the defaults.
     *
     * @throws HtmlRenderingException if an asset type is unsupported, an inline module cannot be neutralized, or a
     * custom attribute is malformed, reserved, duplicated, or carries an unsupported value.
     *
     * @return string The rendered tags joined by the configured separator.
     */
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
     * Builds the final attribute set for one asset, starting from the CSP nonce and applying validated custom
     * attributes on top.
     *
     * Attributes resolving to `null` or `false` are dropped rather than emitted.
     *
     * @param AssetInterface $asset Asset the attributes are computed for.
     * @param HtmlRenderOptions $options Per-render policy supplying the nonce and the custom attributes.
     *
     * @throws HtmlRenderingException if the asset type is unsupported, the attribute provider returns a non-array, or
     * a custom attribute is malformed, reserved, duplicated, or carries an unsupported value.
     *
     * @return array<string, bool|float|int|string|null> Attributes ready to hand to the tag builder.
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

    /**
     * Maps one asset to its HTML5 tag.
     *
     * Module scripts and inline modules become `<script type="module">` elements, stylesheets and preload hints
     * become `<link>` elements. Inline source has its `</script` sequences neutralized before being embedded.
     *
     * @param AssetInterface $asset Asset to render.
     * @param HtmlRenderOptions $options Per-render policy applied to the tag.
     *
     * @throws HtmlRenderingException if the inline source cannot be neutralized, or the asset type is unsupported.
     *
     * @return string The rendered tag.
     */
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
                throw new HtmlRenderingException(
                    Message::INLINE_MODULE_RENDER_FAILED->getMessage(),
                );
            }

            return Script::tag()
                ->attributes($this->attributesFor($asset, $options))
                ->type('module')
                ->html($source)
                ->render();
        }

        throw new HtmlRenderingException(
            Message::ASSET_IMPLEMENTATION_UNSUPPORTED->getMessage(),
        );
    }

    /**
     * Validates one caller-supplied attribute against the renderer's safety rules.
     *
     * Rejects malformed names, the attributes the renderer owns, duplicates within a single tag, every `on*` event
     * handler, `style`, and any value that is not a scalar, `null`, or a finite `float`.
     *
     * @param mixed $name Attribute name supplied by the caller.
     * @param mixed $value Attribute value supplied by the caller.
     * @param array<string, true> $seen Lowercased names already accepted for the current tag.
     *
     * @throws HtmlRenderingException if the name is malformed, reserved, or duplicated, or the value is
     * unsupported.
     *
     * @return array{string, bool|float|int|string|null, string} The name, the value, and the lowercased name.
     */
    private function validateCustomAttribute(mixed $name, mixed $value, array $seen): array
    {
        if (!is_string($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $name) !== 1) {
            throw new HtmlRenderingException(
                Message::HTML_ATTRIBUTE_NAME_INVALID->getMessage(),
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
                Message::HTML_ATTRIBUTE_RESERVED->getMessage($name),
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
                Message::HTML_ATTRIBUTE_VALUE_INVALID->getMessage($name),
            );
        }

        return [$name, $value, $normalizedName];
    }
}
