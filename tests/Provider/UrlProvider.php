<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

/**
 * Data provider for {@see \PHPForge\Vite\Tests\UrlTest} test cases.
 */
final class UrlProvider
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function joinedUrls(): iterable
    {
        yield 'empty base and leading path separator' => ['', '/assets/app.js', 'assets/app.js'];
        yield 'root base and leading path separator' => ['/', '/assets/app.js', '/assets/app.js'];
        yield 'trailing base and leading path separators' => ['/build/', '/assets/app.js', '/build/assets/app.js'];
    }
}
