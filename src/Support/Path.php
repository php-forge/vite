<?php

declare(strict_types=1);

namespace PHPForge\Vite\Support;

use PHPForge\Vite\Exception\{ConfigurationException, Message};

use function preg_match;
use function str_starts_with;

/**
 * Validates filesystem paths required by Vite configuration.
 */
final class Path
{
    /**
     * Validates that the supplied path is a non-empty absolute filesystem path free of control characters.
     *
     * @param string $path Filesystem path to validate.
     * @param string $name Configuration key reported in the exception message.
     *
     * @throws ConfigurationException if the path is empty, relative, or contains control characters.
     *
     * @return string The validated path, returned unchanged.
     */
    public static function requireAbsolute(string $path, string $name): string
    {
        if ($path === '' || self::containsControlCharacter($path) || !self::isAbsolute($path)) {
            throw new ConfigurationException(
                Message::FILESYSTEM_PATH_INVALID->getMessage($name),
            );
        }

        return $path;
    }

    /**
     * Detects ASCII control characters that would make a path unsafe to use in filesystem calls.
     *
     * @param string $value Raw value to inspect.
     *
     * @return bool `true` when the value contains a character in the `\x00-\x1F` or `\x7F` ranges.
     */
    private static function containsControlCharacter(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
    }

    /**
     * Recognizes the absolute path forms accepted on the supported platforms.
     *
     * Accepts POSIX paths (`/var/www`), Windows UNC paths (`\\server\share`), and Windows drive-letter paths
     * (`C:\build` or `C:/build`).
     *
     * @param string $path Filesystem path to classify.
     *
     * @return bool `true` when the path is absolute in any of the recognized forms.
     */
    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
