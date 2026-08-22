<?php

declare(strict_types=1);

namespace PHPForge\Vite\Support;

use PHPForge\Vite\Exception\{InvalidEntrypointException, Message};

use function array_values;
use function is_array;
use function is_string;
use function ltrim;
use function preg_match;
use function str_contains;
use function trim;

final class EntrypointNormalizer
{
    /**
     * @param list<mixed>|string $entrypoints
     *
     * @return list<string>
     */
    public static function normalize(array|string $entrypoints, bool $required): array
    {
        $entrypoints = is_array($entrypoints) ? $entrypoints : [$entrypoints];
        $normalized = [];

        foreach ($entrypoints as $entrypoint) {
            if (!is_string($entrypoint)) {
                throw new InvalidEntrypointException(
                    Message::ENTRYPOINT_TYPE_INVALID->getMessage(),
                );
            }

            $entrypoint = ltrim(trim($entrypoint), '/');

            if (
                $entrypoint === ''
                || str_contains($entrypoint, '\\')
                || preg_match('/[\x00-\x1F\x7F]/', $entrypoint) === 1
            ) {
                throw new InvalidEntrypointException(
                    Message::ENTRYPOINT_INVALID->getMessage(),
                );
            }

            $normalized[$entrypoint] = $entrypoint;
        }

        if ($required && $normalized === []) {
            throw new InvalidEntrypointException(
                Message::ENTRYPOINT_REQUIRED->getMessage(),
            );
        }

        return array_values($normalized);
    }
}
