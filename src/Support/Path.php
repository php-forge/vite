<?php

declare(strict_types=1);

namespace PHPForge\Vite\Support;

use PHPForge\Vite\Exception\ConfigurationException;

use function preg_match;
use function sprintf;
use function str_starts_with;

final class Path
{
    public static function requireAbsolute(string $path, string $name): string
    {
        if ($path === '' || self::containsControlCharacter($path) || !self::isAbsolute($path)) {
            throw new ConfigurationException(
                sprintf('The "%s" value must be an absolute filesystem path.', $name),
            );
        }

        return $path;
    }

    private static function containsControlCharacter(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
