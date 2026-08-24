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

/**
 * Normalizes and validates Vite entrypoint names.
 */
final class EntrypointNormalizer
{
    /**
     * Validates entrypoint values and reduces them to a deduplicated list of relative source paths.
     *
     * Trims each value, strips its leading separator, and preserves insertion order while discarding repeats.
     *
     * @param list<mixed>|string $entrypoints Single entrypoint, or a list of entrypoints to normalize.
     * @param bool $required Whether an empty result must be rejected instead of returned.
     *
     * @throws InvalidEntrypointException if an entrypoint is not a `string`, is empty, contains a backslash or a
     * control character, or if the result is empty while `$required` is `true`.
     *
     * @return list<string> Deduplicated relative entrypoint paths in insertion order.
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
