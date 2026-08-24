<?php

declare(strict_types=1);

namespace PHPForge\Vite\Manifest;

use JsonException;
use PHPForge\Vite\Exception\{
    ConfigurationException,
    InvalidManifestException,
    ManifestNotFoundException,
    ManifestReadException,
    Message,
};
use PHPForge\Vite\Support\{Path, Url};
use stdClass;

use function array_is_list;
use function array_key_exists;
use function clearstatcache;
use function is_array;
use function is_bool;
use function is_file;
use function is_readable;
use function is_string;
use function json_decode;
use function property_exists;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Loads, validates, and caches Vite build manifests from absolute filesystem paths.
 */
final class ManifestLoader
{
    /**
     * @var array<string, array{fingerprint: string, manifest: Manifest}> Parsed manifests keyed by absolute path,
     * each paired with the stat fingerprint that produced it.
     */
    private array $cache = [];

    /**
     * Discards cached manifests so that the next load reads from disk.
     *
     * @param string|null $manifestPath Absolute path of the manifest to forget, or `null` to clear every entry.
     *
     * @throws ConfigurationException if a path is supplied and is not absolute.
     */
    public function clear(string|null $manifestPath = null): void
    {
        if ($manifestPath === null) {
            $this->cache = [];

            return;
        }

        $manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');

        unset($this->cache[$manifestPath]);
    }

    /**
     * Loads, validates, and caches the manifest stored at the supplied absolute path.
     *
     * The cache is keyed by path and invalidated by a stat fingerprint of inode, size, mtime, and ctime, so an
     * unchanged file is parsed once while a rebuilt file is picked up on the next call.
     *
     * @param string $manifestPath Absolute path to the manifest emitted by the Vite build.
     *
     * @throws ConfigurationException if the path is not absolute.
     * @throws InvalidManifestException if the file is not valid JSON, does not decode to an object, or declares an
     * invalid entry.
     * @throws ManifestNotFoundException if no file exists at the path.
     * @throws ManifestReadException if the file cannot be inspected or read.
     *
     * @return Manifest The validated manifest.
     */
    public function load(string $manifestPath): Manifest
    {
        $manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');

        if (!is_file($manifestPath)) {
            throw new ManifestNotFoundException(
                Message::MANIFEST_NOT_FOUND->getMessage($manifestPath),
            );
        }

        clearstatcache(true, $manifestPath);

        $metadata = stat($manifestPath);

        if ($metadata === false) {
            throw new ManifestReadException(
                Message::MANIFEST_INSPECTION_FAILED->getMessage($manifestPath),
            );
        }

        $fingerprint = $this->fingerprint($metadata);
        $cached = $this->cache[$manifestPath] ?? null;

        if ($cached !== null && $cached['fingerprint'] === $fingerprint) {
            return $cached['manifest'];
        }

        if (!is_readable($manifestPath)) {
            throw new ManifestReadException(
                Message::MANIFEST_READ_FAILED->getMessage($manifestPath),
            );
        }

        $content = file_get_contents($manifestPath);

        if ($content === false) {
            throw new ManifestReadException(
                Message::MANIFEST_READ_FAILED->getMessage($manifestPath),
            );
        }

        try {
            $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidManifestException(
                Message::MANIFEST_DECODE_FAILED->getMessage($manifestPath, $exception->getMessage()),
                previous: $exception,
            );
        }

        if (!$decoded instanceof stdClass) {
            throw new InvalidManifestException(
                Message::MANIFEST_ROOT_INVALID->getMessage($manifestPath),
            );
        }

        $manifest = $this->parse($manifestPath, $decoded);

        $this->cache[$manifestPath] = ['fingerprint' => $fingerprint, 'manifest' => $manifest];

        return $manifest;
    }

    /**
     * Builds the change-detection fingerprint of a manifest file from its stat metadata.
     *
     * @param array<int|string, int> $metadata Result of `stat()` for the manifest file.
     *
     * @return string Fingerprint combining inode, size, mtime, and ctime.
     */
    private function fingerprint(array $metadata): string
    {
        return sprintf(
            '%d:%d:%d:%d',
            $metadata['ino'] ?? 0,
            $metadata['size'] ?? 0,
            $metadata['mtime'] ?? 0,
            $metadata['ctime'] ?? 0,
        );
    }

    /**
     * Reads an optional boolean field from a decoded manifest entry.
     *
     * @param stdClass $chunk Decoded manifest entry.
     * @param string $field Field name to read.
     * @param string $key Manifest key reported in the exception message.
     * @param string $manifestPath Manifest path reported in the exception message.
     *
     * @throws InvalidManifestException if the field is present but is not a `bool`.
     *
     * @return bool The field value, or `false` when the field is absent.
     */
    private function optionalBool(stdClass $chunk, string $field, string $key, string $manifestPath): bool
    {
        if (!property_exists($chunk, $field)) {
            return false;
        }

        $value = $chunk->{$field};

        if (!is_bool($value)) {
            throw new InvalidManifestException(
                Message::MANIFEST_FIELD_VALUE_INVALID->getMessage(
                    $key,
                    $manifestPath,
                    $field,
                ),
            );
        }

        return $value;
    }

    /**
     * Reads an optional list-of-strings field from a decoded manifest entry.
     *
     * When `$assetPaths` is `true`, every item is additionally normalized as a relative build path, so a manifest
     * cannot reference a location outside the configured asset base URL.
     *
     * @param stdClass $chunk Decoded manifest entry.
     * @param string $field Field name to read.
     * @param string $key Manifest key reported in the exception message.
     * @param string $manifestPath Manifest path reported in the exception message.
     * @param bool $assetPaths Whether each item must also be a valid relative asset path.
     *
     * @throws InvalidManifestException if the field is not a list, holds a non-string or empty item, or holds an
     * item that is not a valid asset path while `$assetPaths` is `true`.
     *
     * @return list<string> The field items, or an empty list when the field is absent.
     */
    private function optionalList(
        stdClass $chunk,
        string $field,
        string $key,
        string $manifestPath,
        bool $assetPaths,
    ): array {
        if (!property_exists($chunk, $field)) {
            return [];
        }

        $value = $chunk->{$field};

        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidManifestException(
                Message::MANIFEST_FIELD_LIST_INVALID->getMessage(
                    $key,
                    $manifestPath,
                    $field,
                ),
            );
        }

        $items = [];

        foreach ($value as $index => $item) {
            if (!is_string($item) || $item === '') {
                throw new InvalidManifestException(
                    Message::MANIFEST_LIST_ITEM_INVALID->getMessage(
                        $key,
                        $manifestPath,
                        $field,
                        $index,
                    ),
                );
            }

            if ($assetPaths) {
                try {
                    $item = Url::normalizeAssetPath(
                        $item,
                        sprintf('Vite manifest "%s[%d]" value', $field, $index),
                    );
                } catch (ConfigurationException $exception) {
                    throw new InvalidManifestException(
                        Message::MANIFEST_LIST_ITEM_PATH_INVALID->getMessage(
                            $key,
                            $manifestPath,
                            $field,
                            $index,
                        ),
                        previous: $exception,
                    );
                }
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Reads an optional string field from a decoded manifest entry.
     *
     * @param stdClass $chunk Decoded manifest entry.
     * @param string $field Field name to read.
     * @param string $key Manifest key reported in the exception message.
     * @param string $manifestPath Manifest path reported in the exception message.
     *
     * @throws InvalidManifestException if the field is present but is not a `string`.
     *
     * @return string|null The field value, or `null` when the field is absent.
     */
    private function optionalString(stdClass $chunk, string $field, string $key, string $manifestPath): string|null
    {
        if (!property_exists($chunk, $field)) {
            return null;
        }

        $value = $chunk->{$field};

        if (!is_string($value)) {
            throw new InvalidManifestException(
                Message::MANIFEST_FIELD_VALUE_INVALID->getMessage(
                    $key,
                    $manifestPath,
                    $field,
                ),
            );
        }

        return $value;
    }

    /**
     * Converts a decoded manifest object into validated chunks and verifies their cross-references.
     *
     * Every entry is validated first, then a second pass asserts that each `imports` and `dynamicImports` reference
     * resolves to a declared chunk.
     *
     * @param string $manifestPath Manifest path reported in the exception messages.
     * @param stdClass $decoded Decoded manifest root object.
     *
     * @throws InvalidManifestException if an entry is malformed, declares no valid `file`, holds an invalid field,
     * or references a chunk the manifest does not declare.
     *
     * @return Manifest The validated manifest.
     */
    private function parse(string $manifestPath, stdClass $decoded): Manifest
    {
        $chunks = [];

        foreach ((array) $decoded as $key => $value) {
            if (!is_string($key) || $key === '' || !$value instanceof stdClass) {
                throw new InvalidManifestException(
                    Message::MANIFEST_ENTRY_INVALID->getMessage($manifestPath),
                );
            }

            $file = $value->file ?? null;

            if (!is_string($file) || $file === '') {
                throw new InvalidManifestException(
                    Message::MANIFEST_ENTRY_FILE_INVALID->getMessage($key, $manifestPath),
                );
            }

            try {
                $file = Url::normalizeAssetPath($file, 'Vite manifest "file" value');
            } catch (ConfigurationException $exception) {
                throw new InvalidManifestException(
                    Message::MANIFEST_ENTRY_FILE_PATH_INVALID->getMessage($key, $manifestPath),
                    previous: $exception,
                );
            }

            $chunks[$key] = new ManifestChunk(
                key: $key,
                file: $file,
                src: $this->optionalString($value, 'src', $key, $manifestPath),
                css: $this->optionalList($value, 'css', $key, $manifestPath, true),
                assets: $this->optionalList($value, 'assets', $key, $manifestPath, true),
                isEntry: $this->optionalBool($value, 'isEntry', $key, $manifestPath),
                name: $this->optionalString($value, 'name', $key, $manifestPath),
                isDynamicEntry: $this->optionalBool($value, 'isDynamicEntry', $key, $manifestPath),
                imports: $this->optionalList($value, 'imports', $key, $manifestPath, false),
                dynamicImports: $this->optionalList($value, 'dynamicImports', $key, $manifestPath, false),
            );
        }

        foreach ($chunks as $chunk) {
            foreach (['imports' => $chunk->imports, 'dynamicImports' => $chunk->dynamicImports] as $field => $references) {
                foreach ($references as $reference) {
                    if (!array_key_exists($reference, $chunks)) {
                        throw new InvalidManifestException(
                            Message::MANIFEST_REFERENCE_MISSING->getMessage(
                                $chunk->key,
                                $manifestPath,
                                $field,
                                $reference,
                            ),
                        );
                    }
                }
            }
        }

        return new Manifest($chunks);
    }
}
