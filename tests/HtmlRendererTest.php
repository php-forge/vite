<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{AssetCollection, AssetInterface, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\{HtmlRenderingException, Message};
use PHPForge\Vite\Html\{HtmlRenderOptions, HtmlRenderer};
use PHPForge\Vite\Tests\Fixture\UnsupportedAssetStub;
use PHPForge\Vite\Tests\Provider\HtmlRendererProvider;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see HtmlRenderOptions} policies and {@see HtmlRenderer} output.
 *
 * {@see HtmlRendererProvider} for test case data providers.
 */
#[Group('html')]
final class HtmlRendererTest extends TestCase
{
    public function testFactoryCreatesDefaultOptions(): void
    {
        $options = HtmlRenderOptions::create();

        self::assertNull(
            $options->nonce(),
            'The default policy must not emit a nonce.',
        );
        self::assertSame(
            [],
            $options->moduleScriptAttributes(),
            'Module scripts must have no custom attributes by default.',
        );
        self::assertSame(
            [],
            $options->stylesheetAttributes(),
            'Stylesheets must have no custom attributes by default.',
        );
        self::assertSame(
            [],
            $options->modulePreloadAttributes(),
            'Module-preload hints must have no custom attributes by default.',
        );
        self::assertSame(
            [],
            $options->inlineModuleAttributes(),
            'Inline modules must have no custom attributes by default.',
        );
        self::assertSame(
            "\n",
            $options->separator(),
            'Rendered tags must be separated by a newline by default.',
        );
    }

    public function testInlineModuleNeutralizesClosingScriptSequence(): void
    {
        $html = HtmlRenderer::create()->render(
            new AssetCollection([new InlineModule('window.html = "</SCRIPT><p>";')]),
        );

        self::assertStringContainsString(
            '<\\/script><p>',
            $html,
            'The closing sequence must be neutralized.',
        );
        self::assertStringNotContainsString(
            '</SCRIPT><p>',
            $html,
            'Raw closing sequences must not remain.',
        );
    }

    public function testNonceIsAppliedToEveryGeneratedTag(): void
    {
        $assets = new AssetCollection(
            [
                new InlineModule('window.ready = true;'),
                new ModuleScript('/build/app.js'),
                new Stylesheet('/build/app.css'),
                new ModulePreload('/build/vendor.js'),
            ],
        );
        $html = HtmlRenderer::create()->render(
            $assets,
            HtmlRenderOptions::create()->withNonce('c2VjdXJlLW5vbmNl'),
        );

        self::assertSame(
            4,
            substr_count($html, 'nonce="c2VjdXJlLW5vbmNl"'),
            'Every tag must receive the nonce.',
        );
    }

    public function testOptionsModifiersCanResetConfiguredValues(): void
    {
        $configured = HtmlRenderOptions::create()
            ->withNonce('c2VjdXJlLW5vbmNl')
            ->withModuleScriptAttributes(['defer' => true])
            ->withStylesheetAttributes(['media' => 'screen'])
            ->withModulePreloadAttributes(['crossorigin' => 'anonymous'])
            ->withInlineModuleAttributes(['data-inline' => true])
            ->withAttributeProvider(static fn(): array => ['data-provider' => true])
            ->withSeparator('');

        $reset = $configured
            ->withNonce(null)
            ->withModuleScriptAttributes([])
            ->withStylesheetAttributes([])
            ->withModulePreloadAttributes([])
            ->withInlineModuleAttributes([])
            ->withAttributeProvider(null)
            ->withSeparator("\n");

        self::assertNull(
            $reset->nonce(),
            'A `null` nonce must clear the configured nonce.',
        );
        self::assertSame(
            [],
            $reset->moduleScriptAttributes(),
            'An empty array must clear module-script attributes.',
        );
        self::assertSame(
            [],
            $reset->stylesheetAttributes(),
            'An empty array must clear stylesheet attributes.',
        );
        self::assertSame(
            [],
            $reset->modulePreloadAttributes(),
            'An empty array must clear module-preload attributes.',
        );
        self::assertSame(
            [],
            $reset->inlineModuleAttributes(),
            'An empty array must clear inline-module attributes.',
        );
        self::assertSame(
            [],
            $reset->attributesFor(new ModuleScript('/app.js')),
            'A `null` provider must clear the configured attribute provider.',
        );
        self::assertSame(
            "\n",
            $reset->separator(),
            'The separator must be replaceable with its default value.',
        );

        self::assertSame(
            'c2VjdXJlLW5vbmNl',
            $configured->nonce(),
            'Resetting a derived policy must not clear the source nonce.',
        );
        self::assertSame(
            [
                'defer' => true,
                'data-provider' => true,
            ],
            $configured->attributesFor(new ModuleScript('/app.js')),
            'Resetting a derived policy must not clear the source attributes or provider.',
        );
        self::assertSame(
            '',
            $configured->separator(),
            'Resetting a derived policy must not change the source separator.',
        );
    }

    public function testOptionsModifiersReturnNewConfiguredInstances(): void
    {
        $options = HtmlRenderOptions::create();

        $attributeProvider = static fn(AssetInterface $asset): array => [
            'data-asset' => $asset::class,
        ];

        $withAttributeProvider = $options->withAttributeProvider($attributeProvider);
        $withInlineModuleAttributes = $options->withInlineModuleAttributes(['data-inline' => true]);
        $withModulePreloadAttributes = $options->withModulePreloadAttributes(['crossorigin' => 'anonymous']);
        $withModuleScriptAttributes = $options->withModuleScriptAttributes(['defer' => true]);
        $withNonce = $options->withNonce('c2VjdXJlLW5vbmNl');
        $withSeparator = $options->withSeparator('');
        $withStylesheetAttributes = $options->withStylesheetAttributes(['media' => 'screen']);

        self::assertNotSame(
            $options,
            $withAttributeProvider,
            'Configuring an attribute provider must return a new policy.',
        );
        self::assertSame(
            ['data-asset' => ModuleScript::class],
            $withAttributeProvider->attributesFor(new ModuleScript('/app.js')),
            'The configured attribute provider must receive the asset.',
        );
        self::assertNotSame(
            $options,
            $withInlineModuleAttributes,
            'Configuring inline-module attributes must return a new policy.',
        );
        self::assertSame(
            ['data-inline' => true],
            $withInlineModuleAttributes->inlineModuleAttributes(),
            'The configured inline-module attributes must be retained.',
        );
        self::assertSame(
            ['data-inline' => true],
            $withInlineModuleAttributes->attributesFor(new InlineModule('window.ready = true;')),
            'The configured inline-module attributes must be selected for inline modules.',
        );
        self::assertNotSame(
            $options,
            $withModulePreloadAttributes,
            'Configuring module-preload attributes must return a new policy.',
        );
        self::assertSame(
            ['crossorigin' => 'anonymous'],
            $withModulePreloadAttributes->modulePreloadAttributes(),
            'The configured module-preload attributes must be retained.',
        );
        self::assertSame(
            ['crossorigin' => 'anonymous'],
            $withModulePreloadAttributes->attributesFor(new ModulePreload('/vendor.js')),
            'The configured module-preload attributes must be selected for preload hints.',
        );
        self::assertNotSame(
            $options,
            $withModuleScriptAttributes,
            'Configuring module-script attributes must return a new policy.',
        );
        self::assertSame(
            ['defer' => true],
            $withModuleScriptAttributes->moduleScriptAttributes(),
            'The configured module-script attributes must be retained.',
        );
        self::assertSame(
            ['defer' => true],
            $withModuleScriptAttributes->attributesFor(new ModuleScript('/app.js')),
            'The configured module-script attributes must be selected for module scripts.',
        );
        self::assertNotSame(
            $options,
            $withNonce,
            'Configuring a nonce must return a new policy.',
        );
        self::assertSame(
            'c2VjdXJlLW5vbmNl',
            $withNonce->nonce(),
            'The configured nonce must be retained.',
        );
        self::assertNotSame(
            $options,
            $withSeparator,
            'Configuring a separator must return a new policy.',
        );
        self::assertSame(
            '',
            $withSeparator->separator(),
            'The configured separator must be retained.',
        );
        self::assertNotSame(
            $options,
            $withStylesheetAttributes,
            'Configuring stylesheet attributes must return a new policy.',
        );
        self::assertSame(
            ['media' => 'screen'],
            $withStylesheetAttributes->stylesheetAttributes(),
            'The configured stylesheet attributes must be retained.',
        );
        self::assertSame(
            ['media' => 'screen'],
            $withStylesheetAttributes->attributesFor(new Stylesheet('/app.css')),
            'The configured stylesheet attributes must be selected for stylesheets.',
        );

        self::assertNull(
            $options->nonce(),
            'Configuring derived policies must not change the original nonce.',
        );
        self::assertSame(
            [],
            $options->attributesFor(new ModuleScript('/app.js')),
            'Configuring derived policies must not change the original attributes.',
        );
        self::assertSame(
            "\n",
            $options->separator(),
            'Configuring a derived policy must not change the original separator.',
        );
    }

    public function testOptionsRetainConfiguredAttributesWithoutProvider(): void
    {
        $options = HtmlRenderOptions::create()
            ->withModuleScriptAttributes(
                [
                    'crossorigin' => 'anonymous',
                    'defer' => true,
                ],
            );

        self::assertSame(
            [
                'crossorigin' => 'anonymous',
                'defer' => true,
            ],
            $options->attributesFor(new ModuleScript('/app.js')),
            'Every static attribute must be retained.',
        );
    }

    public function testRendererEscapesUrlsAndCustomAttributes(): void
    {
        $script = new ModuleScript('/app.js?x=1&name="quoted"&tag=<value>');
        $options = HtmlRenderOptions::create()
            ->withModuleScriptAttributes(
                [
                    'crossorigin' => 'anonymous',
                    'defer' => true,
                    'data-disabled' => false,
                    'data-empty' => null,
                ],
            )
            ->withAttributeProvider(
                static fn(AssetInterface $asset): array => [
                    'data-kind' => $asset instanceof ModuleScript ? 'module&script' : 'asset',
                ],
            );

        self::assertSame(
            <<<HTML
            <script type="module" src="/app.js?x=1&amp;name=&quot;quoted&quot;&amp;tag=&lt;value&gt;" crossorigin="anonymous" defer data-kind="module&amp;script">
            </script>
            HTML,
            HtmlRenderer::create()->render(new AssetCollection([$script]), $options),
            'URLs, values, booleans, and omitted attributes must be encoded safely.',
        );
    }

    public function testRendererPreservesCollectionOrder(): void
    {
        $assets = new AssetCollection(
            [
                new Stylesheet('/build/app.css'),
                new ModuleScript('/build/app.js'),
                new ModulePreload('/build/vendor.js'),
            ],
        );

        self::assertSame(
            <<<HTML
            <link href="/build/app.css" rel="stylesheet">
            <script type="module" src="/build/app.js">
            </script>
            <link href="/build/vendor.js" rel="modulepreload">
            HTML,
            HtmlRenderer::create()->render($assets),
            'Rendered tags must follow collection order.',
        );
    }

    public function testRendererSupportsCustomSeparator(): void
    {
        $assets = new AssetCollection([new ModuleScript('/one.js'), new ModuleScript('/two.js')]);
        $html = HtmlRenderer::create()->render($assets, HtmlRenderOptions::create()->withSeparator(''));

        self::assertSame(
            <<<HTML
            <script type="module" src="/one.js">
            </script><script type="module" src="/two.js">
            </script>
            HTML,
            $html,
            'The configured separator must join rendered tags.',
        );
    }

    public function testThrowHtmlRenderingExceptionForInvalidAttributeProviderReturn(): void
    {
        $this->expectException(HtmlRenderingException::class);
        $this->expectExceptionMessage(
            Message::HTML_ATTRIBUTE_PROVIDER_RESULT_INVALID->getMessage(),
        );

        $options = HtmlRenderOptions::create()->withAttributeProvider(static fn(): string => 'invalid');

        $options->attributesFor(new ModuleScript('/app.js'));
    }

    public function testThrowHtmlRenderingExceptionForInvalidNonce(): void
    {
        $this->expectException(HtmlRenderingException::class);
        $this->expectExceptionMessage(
            Message::CSP_NONCE_INVALID->getMessage(),
        );

        HtmlRenderOptions::create()->withNonce('invalid nonce');
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @param list<int|string> $arguments
     */
    #[DataProviderExternal(HtmlRendererProvider::class, 'unsafeAttributes')]
    public function testThrowHtmlRenderingExceptionForUnsafeAttribute(
        array $attributes,
        Message $message,
        array $arguments,
    ): void {
        $this->expectException(HtmlRenderingException::class);
        $this->expectExceptionMessage(
            $message->getMessage(...$arguments),
        );

        $options = HtmlRenderOptions::create()->withModuleScriptAttributes($attributes);

        HtmlRenderer::create()->render(new AssetCollection([new ModuleScript('/app.js')]), $options);
    }

    public function testThrowHtmlRenderingExceptionForUnsupportedAssetAttributes(): void
    {
        $this->expectException(HtmlRenderingException::class);
        $this->expectExceptionMessage(
            Message::ASSET_IMPLEMENTATION_UNSUPPORTED->getMessage(),
        );

        HtmlRenderOptions::create()->attributesFor(new UnsupportedAssetStub());
    }
}
