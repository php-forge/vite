<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Asset\{InlineModule, ModuleScript};
use PHPForge\Vite\Configuration\DevelopmentConfiguration;
use PHPForge\Vite\Exception\{InvalidEntrypointException, Message};
use PHPForge\Vite\Tests\Fixtures\CapturingInlineModuleProviderStub;
use PHPForge\Vite\Vite;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Vite} development-server asset resolution.
 */
#[Group('development')]
final class ViteDevelopmentTest extends TestCase
{
    public function testDevelopmentAssetsUseDocumentedOrder(): void
    {
        $provider = new CapturingInlineModuleProviderStub();
        $vite = new Vite(
            new DevelopmentConfiguration(
                devServerUrl: 'http://localhost:5173/',
                entrypoints: ['resources/js/app.js', 'resources/js/admin.js'],
                inlineModuleProviders: [$provider],
            ),
        );

        $assets = $vite->resolve()->all();

        self::assertCount(
            4,
            $assets,
            'The collection must include one inline module and three scripts.',
        );
        self::assertInstanceOf(
            InlineModule::class,
            $assets[0],
            'The application preamble must appear first.',
        );
        self::assertSame(
            'window.devServer = "http://localhost:5173";',
            $assets[0]->source,
            'The preamble must receive the normalized server URL.',
        );
        self::assertInstanceOf(
            ModuleScript::class,
            $assets[1],
            'The Vite client must be a module script.',
        );
        self::assertSame(
            'http://localhost:5173/@vite/client',
            $assets[1]->url,
            'The Vite client must precede application entries.',
        );
        self::assertInstanceOf(
            ModuleScript::class,
            $assets[2],
            'The first entrypoint must be a module script.',
        );
        self::assertSame(
            'http://localhost:5173/resources/js/app.js',
            $assets[2]->url,
            'The first entrypoint URL must be absolute.',
        );
        self::assertInstanceOf(
            ModuleScript::class,
            $assets[3],
            'The second entrypoint must be a module script.',
        );
        self::assertSame(
            'http://localhost:5173/resources/js/admin.js',
            $assets[3]->url,
            'The second entrypoint URL must be absolute.',
        );
        self::assertSame(
            'http://localhost:5173',
            $provider->receivedUrl,
            'The provider must receive the normalized server URL.',
        );
    }

    public function testDevelopmentCanOmitViteClient(): void
    {
        $vite = new Vite(
            new DevelopmentConfiguration(
                devServerUrl: 'http://localhost:5173',
                entrypoints: ['resources/js/app.js'],
                includeViteClient: false,
            ),
        );

        $scripts = $vite->resolve()->moduleScripts();

        self::assertCount(
            1,
            $scripts,
            'Only the application entrypoint must remain.',
        );
        self::assertSame(
            'http://localhost:5173/resources/js/app.js',
            $scripts[0]->url,
            'The application entrypoint URL must be absolute.',
        );
    }

    public function testDevelopmentServerPathPrefixIsPreserved(): void
    {
        $vite = new Vite(
            new DevelopmentConfiguration(
                devServerUrl: 'https://assets.example.com/vite/',
                entrypoints: ['resources/js/app.js'],
            ),
        );

        self::assertSame(
            [
                'https://assets.example.com/vite/@vite/client',
                'https://assets.example.com/vite/resources/js/app.js',
            ],
            array_map(
                static fn(ModuleScript $script): string => $script->url,
                $vite->resolve()->moduleScripts(),
            ),
            'The server path prefix must be included in every URL.',
        );
    }

    public function testResolveAcceptsStringOverrideAndNormalizesLeadingSlash(): void
    {
        $vite = new Vite(new DevelopmentConfiguration('http://localhost:5173'));

        $scripts = $vite->resolve('/resources/js/app.js')->moduleScripts();

        self::assertSame(
            [
                'http://localhost:5173/@vite/client',
                'http://localhost:5173/resources/js/app.js',
            ],
            array_map(static fn(ModuleScript $script): string => $script->url, $scripts),
            'The override must produce the client followed by the normalized entrypoint.',
        );
    }

    public function testResolveDeduplicatesOverrideEntrypoints(): void
    {
        $vite = new Vite(new DevelopmentConfiguration('http://localhost:5173'));

        $scripts = $vite->resolve(['resources/js/app.js', '/resources/js/app.js'])->moduleScripts();

        self::assertCount(
            2,
            $scripts,
            'Equivalent entrypoints must produce one application script.',
        );
    }

    public function testThrowInvalidEntrypointExceptionWhenNoEntrypointIsConfigured(): void
    {
        $vite = new Vite(new DevelopmentConfiguration('http://localhost:5173'));

        $this->expectException(InvalidEntrypointException::class);
        $this->expectExceptionMessage(
            Message::ENTRYPOINT_REQUIRED->getMessage(),
        );

        $vite->resolve();
    }
}
