<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{AssetCollection, AssetInterface, InlineModule, ModulePreload, ModuleScript, Stylesheet};
use PHPForge\Vite\Exception\HtmlRenderingException;
use PHPForge\Vite\Html\{HtmlRenderOptions, HtmlRenderer};
use PHPForge\Vite\Tests\Provider\HtmlRendererProvider;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see HtmlRenderer} output ordering, escaping, attributes, and CSP nonce support.
 *
 * {@see HtmlRendererProvider} for test case data providers.
 */
#[Group('html')]
final class HtmlRendererTest extends TestCase
{
    public function testInlineModuleNeutralizesClosingScriptSequence(): void
    {
        $html = (new HtmlRenderer())->render(
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
        $html = (new HtmlRenderer())->render(
            $assets,
            new HtmlRenderOptions(nonce: 'c2VjdXJlLW5vbmNl'),
        );

        self::assertSame(
            4,
            substr_count($html, 'nonce="c2VjdXJlLW5vbmNl"'),
            'Every tag must receive the nonce.',
        );
    }

    public function testOptionsRetainConfiguredAttributesWithoutProvider(): void
    {
        $options = new HtmlRenderOptions(
            moduleScriptAttributes: [
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
        $options = new HtmlRenderOptions(
            moduleScriptAttributes: [
                'crossorigin' => 'anonymous',
                'defer' => true,
                'data-disabled' => false,
                'data-empty' => null,
            ],
            attributeProvider: static fn(AssetInterface $asset): array => [
                'data-kind' => $asset instanceof ModuleScript ? 'module&script' : 'asset',
            ],
        );

        self::assertSame(
            <<<HTML
            <script type="module" src="/app.js?x=1&amp;name=&quot;quoted&quot;&amp;tag=&lt;value&gt;" crossorigin="anonymous" defer data-kind="module&amp;script">
            </script>
            HTML,
            (new HtmlRenderer())->render(new AssetCollection([$script]), $options),
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
            (new HtmlRenderer())->render($assets),
            'Rendered tags must follow collection order.',
        );
    }

    public function testRendererSupportsCustomSeparator(): void
    {
        $assets = new AssetCollection([new ModuleScript('/one.js'), new ModuleScript('/two.js')]);
        $html = (new HtmlRenderer())->render($assets, new HtmlRenderOptions(separator: ''));

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
            'must return an array',
        );

        $options = (new ReflectionClass(HtmlRenderOptions::class))->newInstanceArgs(
            [null, [], [], [], [], static fn(): string => 'invalid'],
        );

        $options->attributesFor(new ModuleScript('/app.js'));
    }

    public function testThrowHtmlRenderingExceptionForInvalidNonce(): void
    {
        $this->expectException(HtmlRenderingException::class);
        $this->expectExceptionMessage(
            'CSP nonce',
        );

        new HtmlRenderOptions(nonce: 'invalid nonce');
    }

    /**
     * @param array<mixed, mixed> $attributes
     */
    #[DataProviderExternal(HtmlRendererProvider::class, 'unsafeAttributes')]
    public function testThrowHtmlRenderingExceptionForUnsafeAttribute(array $attributes, string $message): void
    {
        $this->expectException(HtmlRenderingException::class);
        $this->expectExceptionMessage(
            $message,
        );

        $options = (new ReflectionClass(HtmlRenderOptions::class))->newInstanceArgs(
            [null, $attributes],
        );

        (new HtmlRenderer())->render(new AssetCollection([new ModuleScript('/app.js')]), $options);
    }
}
