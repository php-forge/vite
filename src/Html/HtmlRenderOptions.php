<?php

declare(strict_types=1);

namespace PHPForge\Vite\Html;

use Closure;
use PHPForge\Vite\Asset\{AssetInterface, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\HtmlRenderingException;

use function array_replace;
use function is_array;
use function preg_match;

/**
 * Immutable per-render HTML policy.
 */
final readonly class HtmlRenderOptions
{
    /**
     * @var (Closure(AssetInterface): array<string, bool|float|int|string|null>)|null
     */
    private Closure|null $attributeProvider;

    /**
     * @param array<string, bool|float|int|string|null> $moduleScriptAttributes
     * @param array<string, bool|float|int|string|null> $stylesheetAttributes
     * @param array<string, bool|float|int|string|null> $modulePreloadAttributes
     * @param array<string, bool|float|int|string|null> $inlineModuleAttributes
     * @param (callable(AssetInterface): array<string, bool|float|int|string|null>)|null $attributeProvider
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
                'The CSP nonce must be a non-empty base64 or base64url value.',
            );
        }

        $this->attributeProvider = $attributeProvider === null ? null : Closure::fromCallable($attributeProvider);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function attributesFor(AssetInterface $asset): array
    {
        $attributes = match (true) {
            $asset instanceof InlineModule => $this->inlineModuleAttributes,
            $asset instanceof ModulePreload => $this->modulePreloadAttributes,
            $asset instanceof ModuleScript => $this->moduleScriptAttributes,
            $asset instanceof Stylesheet => $this->stylesheetAttributes,
            default => throw new HtmlRenderingException(
                'Unsupported AssetInterface implementation.',
            ),
        };

        if (!$this->attributeProvider instanceof Closure) {
            return $attributes;
        }

        $provided = $this->provideAttributes($this->attributeProvider, $asset);

        if (!is_array($provided)) {
            throw new HtmlRenderingException(
                'The HTML attribute provider must return an array.',
            );
        }

        return array_replace($attributes, $provided);
    }

    /**
     * @param Closure(AssetInterface): array<string, bool|float|int|string|null> $provider
     */
    private function provideAttributes(Closure $provider, AssetInterface $asset): mixed
    {
        return $provider($asset);
    }
}
