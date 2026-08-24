<?php

declare(strict_types=1);

namespace PHPForge\Vite\Support;

use PHPForge\Vite\Exception\{ConfigurationException, Message};

use function array_filter;
use function explode;
use function in_array;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_match;
use function rtrim;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;

/**
 * Normalizes and validates development-server and asset URLs.
 */
final class Url
{
    /**
     * Concatenates a base URL and an asset path with exactly one separator between them.
     *
     * Collapses the trailing separators of the base and the leading separators of the path, so that any combination
     * of the two yields a single `/` at the boundary. An empty base returns the path without a leading separator.
     *
     * @param string $baseUrl Normalized base URL, or an empty `string` to emit a relative URL.
     * @param string $path Asset path appended to the base URL.
     *
     * @return string The composed asset URL.
     */
    public static function join(string $baseUrl, string $path): string
    {
        $path = ltrim($path, '/');

        if ($baseUrl === '') {
            return $path;
        }

        return rtrim($baseUrl, '/') . '/' . $path;
    }

    /**
     * Validates a configured asset base URL and strips its trailing separator.
     *
     * Accepts an empty value, a relative prefix, and absolute HTTP(S) URLs. Protocol-relative values, queries, and
     * fragments are rejected because they cannot be composed safely with emitted asset paths. The root value `/` is
     * preserved verbatim.
     *
     * @param string $baseUrl Raw `assetBaseUrl` value supplied by the application.
     *
     * @throws ConfigurationException if the value is malformed, protocol-relative, carries a query or fragment, or
     * uses a scheme other than HTTP(S).
     *
     * @return string The normalized base URL without a trailing separator.
     */
    public static function normalizeAssetBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);

        if (self::containsControlCharacter($baseUrl) || str_starts_with($baseUrl, '//')) {
            throw new ConfigurationException(
                Message::ASSET_BASE_URL_INVALID->getMessage(),
            );
        }

        if (parse_url($baseUrl) === false) {
            throw new ConfigurationException(
                Message::ASSET_BASE_URL_INVALID->getMessage(),
            );
        }

        if (parse_url($baseUrl, PHP_URL_QUERY) !== null || parse_url($baseUrl, PHP_URL_FRAGMENT) !== null) {
            throw new ConfigurationException(
                Message::ASSET_BASE_URL_QUERY_OR_FRAGMENT->getMessage(),
            );
        }

        if (!self::isRelativeOrHttpUrl($baseUrl)) {
            throw new ConfigurationException(
                Message::ASSET_BASE_URL_SCHEME_INVALID->getMessage(),
            );
        }

        if ($baseUrl === '/') {
            return $baseUrl;
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Validates an emitted Vite build path and reduces it to its relative form.
     *
     * Rejects absolute URLs, backslashes, queries, fragments, and `.` or `..` segments, so that a manifest can never
     * escape the configured asset base URL.
     *
     * @param string $path Raw build path taken from the manifest or from configuration.
     * @param string $context Human-readable subject reported in the exception message.
     *
     * @throws ConfigurationException if the path is empty, schemed, unsafe, or contains dot path segments.
     *
     * @return string The path without its leading separator.
     */
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
                Message::ASSET_PATH_INVALID->getMessage($context),
            );
        }

        $segments = array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== '');

        if (in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new ConfigurationException(
                Message::ASSET_PATH_DOT_SEGMENT->getMessage($context),
            );
        }

        return $normalized;
    }

    /**
     * Validates a Vite development-server URL and strips its trailing separator.
     *
     * Requires an absolute HTTP(S) URL with a non-empty host, and rejects queries and fragments so that entrypoint
     * paths can be appended directly.
     *
     * @param string $url Raw `devServerUrl` value supplied by the application.
     *
     * @throws ConfigurationException if the value is not an absolute HTTP(S) URL, or carries a query or fragment.
     *
     * @return string The normalized development-server URL, including any path prefix.
     */
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
                Message::DEVELOPMENT_SERVER_URL_INVALID->getMessage(),
            );
        }

        return $url;
    }

    /**
     * Validates a resolved asset URL before it reaches an asset value object.
     *
     * Guards the boundary where a URL becomes renderable: empty values, protocol-relative values, and non-HTTP(S)
     * schemes such as `javascript:` are rejected, while relative URLs are accepted unchanged.
     *
     * @param string $url Resolved asset URL to validate.
     *
     * @throws ConfigurationException if the URL is empty, malformed, protocol-relative, or uses a scheme other than
     * HTTP(S).
     *
     * @return string The validated URL, trimmed of surrounding whitespace.
     */
    public static function requireSafeAssetUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || self::containsControlCharacter($url) || str_starts_with($url, '//')) {
            throw new ConfigurationException(
                Message::ASSET_URL_FORM_UNSAFE->getMessage(),
            );
        }

        if (parse_url($url) === false) {
            throw new ConfigurationException(
                Message::ASSET_URL_FORM_INVALID->getMessage(),
            );
        }

        if (!self::isRelativeOrHttpUrl($url)) {
            throw new ConfigurationException(
                Message::ASSET_URL_SCHEME_INVALID->getMessage(),
            );
        }

        return $url;
    }

    /**
     * Detects ASCII control characters that would make a URL unsafe to emit into markup.
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
     * Classifies a URL as either scheme-less or an absolute HTTP(S) URL with a host.
     *
     * @param string $url URL to classify.
     *
     * @return bool `true` for relative URLs and for absolute `http`/`https` URLs, `false` for any other scheme.
     */
    private static function isRelativeOrHttpUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme === null) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($scheme)
            && is_string($host)
            && $host !== ''
            && in_array(strtolower($scheme), ['http', 'https'], true);
    }
}
