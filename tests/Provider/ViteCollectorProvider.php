<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

use PHPForge\Vite\Vite;

/**
 * Data provider for {@see \PHPForge\Vite\Tests\Debug\ViteCollectorTest} test cases.
 */
final class ViteCollectorProvider
{
    /**
     * Development server URL shared by the provided captures and the configuration under test.
     */
    public const string DEV_SERVER_URL = 'http://127.0.0.1:1';

    /**
     * @return iterable<string, array{list<list<string>|string>, array<string, mixed>}>
     */
    public static function developmentCaptures(): iterable
    {
        yield 'single resolution' => [
            ['second.js'],
            ['components' => [self::component(1, ['second.js'])]],
        ];
        yield 'trimmed and deduplicated entrypoints' => [
            [[' /app.js ', 'app.js']],
            ['components' => [self::component(1, ['app.js'])]],
        ];
        yield 'two resolutions in one cycle' => [
            [[' /app.js ', 'app.js'], 'second.js'],
            ['components' => [self::component(1, ['app.js']), self::component(2, ['second.js'])]],
        ];
    }

    /**
     * Describes one observed development resolution.
     *
     * @param int $number Position of the resolution in the cycle.
     * @param list<string> $entrypoints Normalized entrypoints of the resolution.
     *
     * @return array<string, mixed> Captured component payload.
     */
    private static function component(int $number, array $entrypoints): array
    {
        return [
            'id' => "vite-{$number}",
            'class' => Vite::class,
            'implementation' => 'modern',
            'inspectionAvailable' => true,
            'mode' => 'development',
            'entrypoints' => $entrypoints,
            'baseUrl' => '',
            'devServerUrl' => self::DEV_SERVER_URL,
            'manifestPath' => '',
            'includeViteClient' => false,
            'modulePreload' => null,
            'chunks' => [],
        ];
    }
}
