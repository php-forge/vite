<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

/**
 * Data provider for {@see ConfigurationTest} test cases.
 */
final class ConfigurationProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDevelopmentServerUrls(): iterable
    {
        yield 'empty' => [''];
        yield 'relative' => ['/vite'];
        yield 'unsafe scheme' => ['javascript:alert(1)'];
        yield 'unsupported absolute scheme' => ['ftp://localhost:5173'];
        yield 'control character' => ["https://localhost:5173/vite\nclient"];
        yield 'query' => ['https://localhost:5173/?token=value'];
        yield 'fragment' => ['https://localhost:5173/#client'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEntrypoints(): iterable
    {
        yield 'blank' => ['  '];
        yield 'backslash' => ['resources\\js\\app.js'];
        yield 'control character' => ["resources/js/app.js\nadmin.js"];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidProductionBaseUrls(): iterable
    {
        yield 'unsafe scheme' => ['javascript:alert(1)', 'HTTP(S)'];
        yield 'unsupported absolute scheme' => ['ftp://cdn.example.com/build', 'HTTP(S)'];
        yield 'scheme relative' => ['//cdn.example.com/build', 'invalid'];
        yield 'control character' => ["/build\nassets", 'invalid'];
        yield 'query' => ['/build?token=value', 'query or fragment'];
        yield 'fragment' => ['/build#assets', 'query or fragment'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafeAssetUrls(): iterable
    {
        yield 'empty' => ['', 'safe URL form'];
        yield 'control character' => ["/build/app.js\nmodule", 'safe URL form'];
        yield 'scheme relative' => ['//cdn.example.com/app.js', 'safe URL form'];
        yield 'data scheme' => ['data:text/javascript,alert(1)', 'HTTP(S)'];
        yield 'unsupported absolute scheme' => ['ftp://cdn.example.com/app.js', 'HTTP(S)'];
    }
}
