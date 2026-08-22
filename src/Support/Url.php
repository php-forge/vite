<?php

declare(strict_types=1);

namespace PHPForge\Vite\Support;

use PHPForge\Vite\Exception\ConfigurationException;

use function array_filter;
use function explode;
use function in_array;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;

final class Url
{
    public static function join(string $baseUrl, string $path): string
    {
        $path = ltrim($path, '/');

        if ($baseUrl === '') {
            return $path;
        }

        return rtrim($baseUrl, '/') . '/' . $path;
    }

    public static function normalizeAssetBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);

        if (self::containsControlCharacter($baseUrl) || str_starts_with($baseUrl, '//')) {
            throw new ConfigurationException(
                'The "assetBaseUrl" value is invalid.',
            );
        }

        if (parse_url($baseUrl) === false) {
            throw new ConfigurationException(
                'The "assetBaseUrl" value is invalid.',
            );
        }

        if (parse_url($baseUrl, PHP_URL_QUERY) !== null || parse_url($baseUrl, PHP_URL_FRAGMENT) !== null) {
            throw new ConfigurationException(
                'The "assetBaseUrl" value must not contain a query or fragment.',
            );
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);

        if ($scheme !== null) {
            $host = parse_url($baseUrl, PHP_URL_HOST);

            if (
                !is_string($scheme)
                || !is_string($host)
                || $host === ''
                || !in_array(strtolower($scheme), ['http', 'https'], true)
            ) {
                throw new ConfigurationException(
                    'The "assetBaseUrl" value must use HTTP(S) when it is an absolute URL.',
                );
            }
        }

        if ($baseUrl === '/') {
            return $baseUrl;
        }

        return rtrim($baseUrl, '/');
    }

    public static function normalizeAssetPath(string $path, string $context): string
    {
        $normalized = ltrim(trim($path), '/');

        if (
            $normalized === ''
            || self::containsControlCharacter($normalized)
            || str_contains($normalized, '\\')
            || str_contains($normalized, '?')
            || str_contains($normalized, '#')
            || preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $normalized) === 1
        ) {
            throw new ConfigurationException(
                sprintf('The %s must be a relative Vite build path.', $context),
            );
        }

        $segments = array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== '');

        if (in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new ConfigurationException(
                sprintf('The %s must not contain dot path segments.', $context),
            );
        }

        return $normalized;
    }

    public static function normalizeDevServerUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (
            self::containsControlCharacter($url)
            || !is_string($scheme)
            || !is_string($host)
            || $host === ''
            || !in_array(strtolower($scheme), ['http', 'https'], true)
            || parse_url($url, PHP_URL_QUERY) !== null
            || parse_url($url, PHP_URL_FRAGMENT) !== null
        ) {
            throw new ConfigurationException(
                'The "devServerUrl" value must be an absolute HTTP(S) URL.',
            );
        }

        return $url;
    }

    public static function requireSafeAssetUrl(string $url): string
    {
        if ($url === '' || self::containsControlCharacter($url) || str_starts_with($url, '//')) {
            throw new ConfigurationException(
                'Asset URLs must be non-empty and use a safe URL form.',
            );
        }

        if (parse_url($url) === false) {
            throw new ConfigurationException(
                'Asset URLs must use a valid URL form.',
            );
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== null) {
            $host = parse_url($url, PHP_URL_HOST);

            if (
                !is_string($scheme)
                || !is_string($host)
                || $host === ''
                || !in_array(strtolower($scheme), ['http', 'https'], true)
            ) {
                throw new ConfigurationException(
                    'Absolute asset URLs must use HTTP(S).',
                );
            }
        }

        return $url;
    }

    private static function containsControlCharacter(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
    }
}
