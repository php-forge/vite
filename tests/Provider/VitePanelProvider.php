<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

use PHPForge\Vite\Exception\Message;

use function array_replace;

/**
 * Data provider for {@see \PHPForge\Vite\Tests\Debug\VitePanelTest} test cases.
 */
final class VitePanelProvider
{
    /**
     * Builds the captured components of one valid Vite integration.
     *
     * @param string $mode Captured resolution mode.
     * @param array<string, mixed> $overrides Fields replacing the valid ones.
     *
     * @return non-empty-list<array<string, mixed>> Captured component list.
     */
    public static function components(string $mode, array $overrides = []): array
    {
        return [
            array_replace(
                [
                    'id' => 'frontend',
                    'class' => 'Example\\Vite',
                    'implementation' => 'modern',
                    'inspectionAvailable' => true,
                    'mode' => $mode,
                    'entrypoints' => ['app.js'],
                    'baseUrl' => '',
                    'devServerUrl' => null,
                    'manifestPath' => '',
                    'includeViteClient' => true,
                    'modulePreload' => false,
                    'chunks' => [],
                ],
                $overrides,
            ),
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function malformedCaptures(): iterable
    {
        $listRequired = Message::DIAGNOSTICS_COMPONENT_LIST_REQUIRED->getMessage();
        $chunkInvalid = Message::DIAGNOSTICS_CHUNK_INVALID->getMessage();

        yield 'missing component list' => [
            [],
            $listRequired,
        ];
        yield 'component list is not an array' => [
            ['components' => false],
            $listRequired,
        ];
        yield 'component list is not a list' => [
            ['components' => ['invalid' => self::components('unknown')[0]]],
            $listRequired,
        ];
        yield 'component is not an array' => [
            ['components' => [false]],
            Message::DIAGNOSTICS_COMPONENT_INVALID->getMessage(),
        ];
        yield 'mode is not a string' => [
            ['components' => self::components('unknown', ['mode' => 42])],
            Message::DIAGNOSTICS_VALUE_NOT_STRING->getMessage('mode'),
        ];
        yield 'mode is unknown' => [
            ['components' => self::components('invalid')],
            Message::DIAGNOSTICS_MODE_UNKNOWN->getMessage(),
        ];
        yield 'inspection availability is not a boolean' => [
            ['components' => self::components('unknown', ['inspectionAvailable' => 1])],
            Message::DIAGNOSTICS_INSPECTION_INVALID->getMessage(),
        ];
        yield 'entrypoints are not a list' => [
            ['components' => self::components('unknown', ['entrypoints' => false])],
            Message::DIAGNOSTICS_ENTRYPOINT_LIST_INVALID->getMessage(),
        ];
        yield 'entrypoint is not a string' => [
            ['components' => self::components('unknown', ['entrypoints' => [false]])],
            Message::DIAGNOSTICS_ENTRYPOINT_TYPE_INVALID->getMessage(),
        ];
        yield 'development server URL is not a string' => [
            ['components' => self::components('unknown', ['devServerUrl' => 42])],
            Message::DIAGNOSTICS_VALUE_NOT_NULLABLE_STRING->getMessage('devServerUrl'),
        ];
        yield 'Vite client flag is not a boolean' => [
            ['components' => self::components('unknown', ['includeViteClient' => 'yes'])],
            Message::DIAGNOSTICS_FLAG_INVALID->getMessage(),
        ];
        yield 'chunks are not a list' => [
            ['components' => self::components('unknown', ['chunks' => false])],
            Message::DIAGNOSTICS_CHUNK_LIST_INVALID->getMessage(),
        ];
        yield 'chunks are keyed instead of a list' => [
            ['components' => self::components('unknown', ['chunks' => ['first' => self::chunk()]])],
            Message::DIAGNOSTICS_CHUNK_LIST_INVALID->getMessage(),
        ];
        yield 'entrypoints are keyed instead of a list' => [
            ['components' => self::components('unknown', ['entrypoints' => ['first' => 'app.js']])],
            Message::DIAGNOSTICS_ENTRYPOINT_LIST_INVALID->getMessage(),
        ];
        yield 'chunk is not an array' => [
            ['components' => self::components('unknown', ['chunks' => [false]])],
            $chunkInvalid,
        ];
        yield 'chunk carries no fields' => [
            ['components' => self::components('unknown', ['chunks' => [[]]])],
            $chunkInvalid,
        ];
        yield 'chunk carries no imports' => [
            ['components' => self::components('unknown', ['chunks' => [['cssCount' => 0]]])],
            $chunkInvalid,
        ];
        yield 'chunk carries no entry flag' => [
            ['components' => self::components('unknown', ['chunks' => [['cssCount' => 0, 'imports' => 0]]])],
            $chunkInvalid,
        ];
        yield 'chunk CSS count is not an integer' => [
            ['components' => self::components('unknown', ['chunks' => [self::chunk(['cssCount' => 'invalid'])]])],
            $chunkInvalid,
        ];
        yield 'chunk imports are not an integer' => [
            ['components' => self::components('unknown', ['chunks' => [self::chunk(['imports' => 'invalid'])]])],
            $chunkInvalid,
        ];
        yield 'chunk entry flag is not a boolean' => [
            ['components' => self::components('unknown', ['chunks' => [self::chunk(['isEntry' => 'invalid'])]])],
            $chunkInvalid,
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function modes(): iterable
    {
        yield 'development' => ['development', 'Development'];
        yield 'production' => ['production', 'Production'];
        yield 'unknown' => ['unknown', 'Unknown'];
    }

    /**
     * Builds one valid production-manifest chunk.
     *
     * @param array<string, mixed> $overrides Fields replacing the valid ones.
     *
     * @return array<string, mixed> Captured chunk payload.
     */
    private static function chunk(array $overrides = []): array
    {
        return array_replace(
            ['name' => 'app.js', 'file' => 'app.js', 'cssCount' => 0, 'imports' => 0, 'isEntry' => true],
            $overrides,
        );
    }
}
