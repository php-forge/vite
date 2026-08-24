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
final readonly class HtmlRenderOptions
{
    /**
     * @var (Closure(AssetInterface): array<string, bool|float|int|string|null>)|null Per-asset attribute callback,
     * or `null` when only the static per-type attributes apply.
     */
    private Closure|null $attributeProvider;

    /**
     * @param string|null $nonce CSP nonce applied to every generated tag, or `null` to emit none.
     * @param array<string, bool|float|int|string|null> $moduleScriptAttributes Extra attributes for module scripts.
     * @param array<string, bool|float|int|string|null> $stylesheetAttributes Extra attributes for stylesheets.
     * @param array<string, bool|float|int|string|null> $modulePreloadAttributes Extra attributes for preload hints.
     * @param array<string, bool|float|int|string|null> $inlineModuleAttributes Extra attributes for inline modules.
     * @param (callable(AssetInterface): array<string, bool|float|int|string|null>)|null $attributeProvider Callback
     * returning per-asset attributes that override the per-type ones, or `null` to apply none.
     * @param string $separator String inserted between two rendered tags.
     *
     * @throws HtmlRenderingException if the nonce is not a non-empty base64 or base64url value.
     */
    public function __construct(
        public string|null $nonce = null,
        public array $moduleScriptAttributes = [],
        public array $stylesheetAttributes = [],
        public array $modulePreloadAttributes = [],
        public array $inlineModuleAttributes = [],
        callable|null $attributeProvider = null,
        public string $separator = "\n",
    ) {
        if ($nonce !== null && preg_match('/^[A-Za-z0-9+\/_-]+={0,2}$/', $nonce) !== 1) {
            throw new HtmlRenderingException(
                Message::CSP_NONCE_INVALID->getMessage(),
            );
        }

        $this->attributeProvider = $attributeProvider === null ? null : Closure::fromCallable($attributeProvider);
    }

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
     * Invokes the attribute provider without narrowing its result.
     *
     * The return type stays `mixed` so the caller can reject a provider that breaks its declared contract at
     * runtime, which static analysis alone cannot guarantee.
     *
     * @param Closure(AssetInterface): array<string, bool|float|int|string|null> $provider Configured callback.
     * @param AssetInterface $asset Asset passed to the callback.
     *
     * @return mixed Whatever the provider returned.
     */
    private function provideAttributes(Closure $provider, AssetInterface $asset): mixed
    {
        return $provider($asset);
    }
}
