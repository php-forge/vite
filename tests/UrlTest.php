<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Support\Url;
use PHPForge\Vite\Tests\Provider\UrlProvider;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Url} base URL and asset path composition.
 *
 * {@see UrlProvider} for test case data providers.
 */
#[Group('url')]
final class UrlTest extends TestCase
{
    #[DataProviderExternal(UrlProvider::class, 'joinedUrls')]
    public function testJoinNormalizesBoundarySeparators(string $baseUrl, string $path, string $expected): void
    {
        self::assertSame(
            $expected,
            Url::join($baseUrl, $path),
            'The base and path must be separated by one slash.',
        );
    }
}
