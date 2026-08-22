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
        yield 'query' => ['https://localhost:5173/?token=value'];
        yield 'fragment' => ['https://localhost:5173/#client'];
    }
}
