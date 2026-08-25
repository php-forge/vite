<?php

declare(strict_types=1);

namespace PHPForge\Vite\Html;

use Closure;
use PHPForge\Vite\Asset\{AssetInterface, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\{HtmlRenderingException, Message};

use function array_replace;
use function is_array;
use function preg_match;

/**
 * Immutable per-render HTML policy.
 */
final class HtmlRenderOptions
{
    /**
     * @var (Closure(AssetInterface): mixed)|null Per-asset attribute callback, or `null` when only the static per-type
     * attributes apply.
     */
    private Closure|null $attributeProvider = null;

    /**
     * @var array<array-key, mixed> Extra attributes for inline modules.
     */
    private array $inlineModuleAttributes = [];

    /**
     * @var array<array-key, mixed> Extra attributes for preload hints.
     */
    private array $modulePreloadAttributes = [];

    /**
     * @var array<array-key, mixed> Extra attributes for module scripts.
     */
    private array $moduleScriptAttributes = [];

    /**
     * CSP nonce applied to every generated tag, or `null` to emit none.
     */
    private string|null $nonce = null;

    /**
     * String inserted between two rendered tags.
     */
    private string $separator = "\n";

    /**
     * @var array<array-key, mixed> Extra attributes for stylesheets.
     */
    private array $stylesheetAttributes = [];

    /**
     * Returns the attributes configured for the supplied asset.
     *
     * Starts from the attributes registered for the asset's type and, when a provider is configured, overlays the
     * values it returns for that asset.
     *
     * @param AssetInterface $asset Asset the attributes are resolved for.
     *
     * @throws HtmlRenderingException if the asset type is unsupported, or the provider returns a non-array.
     *
     * @return array<array-key, mixed> Raw attributes, still to be validated by the renderer.
     */
    public function attributesFor(AssetInterface $asset): array
    {
        $attributes = match (true) {
            $asset instanceof InlineModule => $this->inlineModuleAttributes,
            $asset instanceof ModulePreload => $this->modulePreloadAttributes,
            $asset instanceof ModuleScript => $this->moduleScriptAttributes,
            $asset instanceof Stylesheet => $this->stylesheetAttributes,
            default => throw new HtmlRenderingException(
                Message::ASSET_IMPLEMENTATION_UNSUPPORTED->getMessage(),
            ),
        };

        if (!$this->attributeProvider instanceof Closure) {
            return $attributes;
        }

        $provided = $this->provideAttributes($this->attributeProvider, $asset);

        if (!is_array($provided)) {
            throw new HtmlRenderingException(
                Message::HTML_ATTRIBUTE_PROVIDER_RESULT_INVALID->getMessage(),
            );
        }

        return array_replace($attributes, $provided);
    }

    /**
     * Creates an HTML render policy with the default settings.
     *
     * @return HtmlRenderOptions A new default render policy.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Returns the attributes configured for inline modules.
     *
     * @return array<array-key, mixed> Extra attributes for inline modules.
     */
    public function inlineModuleAttributes(): array
    {
        return $this->inlineModuleAttributes;
    }

    /**
     * Returns the attributes configured for module-preload hints.
     *
     * @return array<array-key, mixed> Extra attributes for module-preload hints.
     */
    public function modulePreloadAttributes(): array
    {
        return $this->modulePreloadAttributes;
    }

    /**
     * Returns the attributes configured for module scripts.
     *
     * @return array<array-key, mixed> Extra attributes for module scripts.
     */
    public function moduleScriptAttributes(): array
    {
        return $this->moduleScriptAttributes;
    }

    /**
     * Returns the CSP nonce applied to every generated tag.
     *
     * @return string|null The configured nonce, or `null` when none is emitted.
     */
    public function nonce(): string|null
    {
        return $this->nonce;
    }

    /**
     * Returns the string inserted between rendered tags.
     *
     * @return string The configured tag separator.
     */
    public function separator(): string
    {
        return $this->separator;
    }

    /**
     * Returns the attributes configured for stylesheets.
     *
     * @return array<array-key, mixed> Extra attributes for stylesheets.
     */
    public function stylesheetAttributes(): array
    {
        return $this->stylesheetAttributes;
    }

    /**
     * Returns a new policy with the per-asset attribute provider replaced.
     *
     * @param (callable(AssetInterface): mixed)|null $attributeProvider Callback returning per-asset attributes that
     * override the per-type ones, or `null` to apply none.
     *
     * @return HtmlRenderOptions A new policy containing the supplied provider.
     */
    public function withAttributeProvider(callable|null $attributeProvider): self
    {
        $clone = clone $this;
        $clone->attributeProvider = $attributeProvider === null ? null : Closure::fromCallable($attributeProvider);

        return $clone;
    }

    /**
     * Returns a new policy with the inline-module attributes replaced.
     *
     * @param array<array-key, mixed> $attributes Extra attributes for inline modules.
     *
     * @return HtmlRenderOptions A new policy containing the supplied attributes.
     */
    public function withInlineModuleAttributes(array $attributes): self
    {
        $clone = clone $this;
        $clone->inlineModuleAttributes = $attributes;

        return $clone;
    }

    /**
     * Returns a new policy with the module-preload attributes replaced.
     *
     * @param array<array-key, mixed> $attributes Extra attributes for module-preload hints.
     *
     * @return HtmlRenderOptions A new policy containing the supplied attributes.
     */
    public function withModulePreloadAttributes(array $attributes): self
    {
        $clone = clone $this;
        $clone->modulePreloadAttributes = $attributes;

        return $clone;
    }

    /**
     * Returns a new policy with the module-script attributes replaced.
     *
     * @param array<array-key, mixed> $attributes Extra attributes for module scripts.
     *
     * @return HtmlRenderOptions A new policy containing the supplied attributes.
     */
    public function withModuleScriptAttributes(array $attributes): self
    {
        $clone = clone $this;
        $clone->moduleScriptAttributes = $attributes;

        return $clone;
    }

    /**
     * Returns a new policy with the CSP nonce replaced.
     *
     * @param string|null $nonce CSP nonce applied to every generated tag, or `null` to emit none.
     *
     * @throws HtmlRenderingException if the nonce is not a non-empty base64 or base64url value.
     *
     * @return HtmlRenderOptions A new policy containing the supplied nonce.
     */
    public function withNonce(string|null $nonce): self
    {
        if ($nonce !== null && preg_match('/^[A-Za-z0-9+\/_-]+={0,2}$/', $nonce) !== 1) {
            throw new HtmlRenderingException(
                Message::CSP_NONCE_INVALID->getMessage(),
            );
        }

        $clone = clone $this;
        $clone->nonce = $nonce;

        return $clone;
    }

    /**
     * Returns a new policy with the tag separator replaced.
     *
     * @param string $separator String inserted between two rendered tags.
     *
     * @return HtmlRenderOptions A new policy containing the supplied separator.
     */
    public function withSeparator(string $separator): self
    {
        $clone = clone $this;
        $clone->separator = $separator;

        return $clone;
    }

    /**
     * Returns a new policy with the stylesheet attributes replaced.
     *
     * @param array<array-key, mixed> $attributes Extra attributes for stylesheets.
     *
     * @return HtmlRenderOptions A new policy containing the supplied attributes.
     */
    public function withStylesheetAttributes(array $attributes): self
    {
        $clone = clone $this;
        $clone->stylesheetAttributes = $attributes;

        return $clone;
    }

    /**
     * Invokes the attribute provider without narrowing its result.
     *
     * The return type stays `mixed` so the caller can validate the provider result at runtime.
     *
     * @param Closure(AssetInterface): mixed $provider Configured callback.
     * @param AssetInterface $asset Asset passed to the callback.
     *
     * @return mixed Whatever the provider returned.
     */
    private function provideAttributes(Closure $provider, AssetInterface $asset): mixed
    {
        return $provider($asset);
    }
}
