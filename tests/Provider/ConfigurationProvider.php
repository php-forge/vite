<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

use PHPForge\Vite\Exception\Message;

/**
 * Data provider for {@see \PHPForge\Vite\Tests\ConfigurationTest} test cases.
 */
final class ConfigurationProvider
{
    /**
     * @return iterable<string, array{string, Message}>
     */
    public static function invalidDevelopmentServerUrls(): iterable
    {
        yield 'empty' => [
            '',
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
        yield 'relative' => [
            '/vite',
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
        yield 'unsafe scheme' => [
            'javascript:alert(1)',
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
        yield 'unsupported absolute scheme' => [
            'ftp://localhost:5173',
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
        yield 'control character' => [
            "https://localhost:5173/vite\nclient",
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
        yield 'query' => [
            'https://localhost:5173/?token=value',
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
        yield 'fragment' => [
            'https://localhost:5173/#client',
            Message::DEVELOPMENT_SERVER_URL_INVALID,
        ];
    }

    /**
     * @return iterable<string, array{string, Message}>
     */
    public static function invalidEntrypoints(): iterable
    {
        yield 'blank' => [
            '  ',
            Message::ENTRYPOINT_INVALID,
        ];
        yield 'backslash' => [
            'resources\\js\\app.js',
            Message::ENTRYPOINT_INVALID,
        ];
        yield 'control character' => [
            "resources/js/app.js\nadmin.js",
            Message::ENTRYPOINT_INVALID,
        ];
    }

    /**
     * @return iterable<string, array{string, Message}>
     */
    public static function invalidProductionBaseUrls(): iterable
    {
        yield 'malformed absolute URL' => [
            'http://:',
            Message::ASSET_BASE_URL_INVALID,
        ];
        yield 'unsafe scheme' => [
            'javascript:alert(1)',
            Message::ASSET_BASE_URL_SCHEME_INVALID,
        ];
        yield 'unsupported absolute scheme' => [
            'ftp://cdn.example.com/build',
            Message::ASSET_BASE_URL_SCHEME_INVALID,
        ];
        yield 'scheme relative' => [
            '//cdn.example.com/build',
            Message::ASSET_BASE_URL_INVALID,
        ];
        yield 'control character' => [
            "/build\nassets",
            Message::ASSET_BASE_URL_INVALID,
        ];
        yield 'query' => [
            '/build?token=value',
            Message::ASSET_BASE_URL_QUERY_OR_FRAGMENT,
        ];
        yield 'fragment' => [
            '/build#assets',
            Message::ASSET_BASE_URL_QUERY_OR_FRAGMENT,
        ];
    }

    /**
     * @return iterable<string, array{string, Message}>
     */
    public static function unsafeAssetUrls(): iterable
    {
        yield 'malformed absolute URL' => [
            'http://:',
            Message::ASSET_URL_FORM_INVALID,
        ];
        yield 'empty' => [
            '',
            Message::ASSET_URL_FORM_UNSAFE,
        ];
        yield 'control character' => [
            "/build/app.js\nmodule",
            Message::ASSET_URL_FORM_UNSAFE,
        ];
        yield 'scheme relative' => [
            '//cdn.example.com/app.js',
            Message::ASSET_URL_FORM_UNSAFE,
        ];
        yield 'data scheme' => [
            'data:text/javascript,alert(1)',
            Message::ASSET_URL_SCHEME_INVALID,
        ];
        yield 'leading whitespace unsafe scheme' => [
            ' javascript:alert(1)',
            Message::ASSET_URL_SCHEME_INVALID,
        ];
        yield 'unsupported absolute scheme' => [
            'ftp://cdn.example.com/app.js',
            Message::ASSET_URL_SCHEME_INVALID,
        ];
    }
}
