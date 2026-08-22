<?php

declare(strict_types=1);

namespace PHPForge\Vite\Manifest;

use JsonException;
use PHPForge\Vite\Exception\{
    ConfigurationException,
    InvalidManifestException,
    ManifestNotFoundException,
    ManifestReadException,
};
use PHPForge\Vite\Support\{Path, Url};
use stdClass;

use function array_is_list;
use function array_key_exists;
use function clearstatcache;
use function file_get_contents;
use function is_array;
use function is_bool;
use function is_file;
use function is_readable;
use function is_string;
use function json_decode;
use function property_exists;
use function sprintf;
use function stat;

use const JSON_THROW_ON_ERROR;

/**
 * Loads, validates, and caches Vite build manifests from absolute filesystem paths.
 */
final class ManifestLoader
{
    /**
     * @var array<string, array{fingerprint: string, manifest: Manifest}>
     */
    private array $cache = [];

    public function clear(string|null $manifestPath = null): void
    {
        if ($manifestPath === null) {
            $this->cache = [];

            return;
        }

        $manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');

        unset($this->cache[$manifestPath]);
    }

    public function load(string $manifestPath): Manifest
    {
        $manifestPath = Path::requireAbsolute($manifestPath, 'manifestPath');

        if (!is_file($manifestPath)) {
            throw new ManifestNotFoundException(
                sprintf('The Vite manifest file "%s" does not exist.', $manifestPath),
            );
        }

        clearstatcache(true, $manifestPath);
        $metadata = stat($manifestPath);

        if ($metadata === false) {
            throw new ManifestReadException(
                sprintf('Unable to inspect the Vite manifest file "%s".', $manifestPath),
            );
        }

        $fingerprint = $this->fingerprint($metadata);
        $cached = $this->cache[$manifestPath] ?? null;

        if ($cached !== null && $cached['fingerprint'] === $fingerprint) {
            return $cached['manifest'];
        }

        if (!is_readable($manifestPath)) {
            throw new ManifestReadException(
                sprintf('Unable to read the Vite manifest file "%s".', $manifestPath),
            );
        }

        $content = file_get_contents($manifestPath);

        if ($content === false) {
            throw new ManifestReadException(
                sprintf('Unable to read the Vite manifest file "%s".', $manifestPath),
            );
        }

        try {
            $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidManifestException(
                sprintf('Unable to decode the Vite manifest file "%s": %s', $manifestPath, $exception->getMessage()),
                previous: $exception,
            );
        }

        if (!$decoded instanceof stdClass) {
            throw new InvalidManifestException(
                sprintf('The Vite manifest file "%s" must contain a JSON object.', $manifestPath),
            );
        }

        $manifest = $this->parse($manifestPath, $decoded);
        $this->cache[$manifestPath] = ['fingerprint' => $fingerprint, 'manifest' => $manifest];

        return $manifest;
    }

    /**
     * @param array<int|string, int> $metadata
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

    private function optionalBool(stdClass $chunk, string $field, string $key, string $manifestPath): bool
    {
        if (!property_exists($chunk, $field)) {
            return false;
        }

        $value = $chunk->{$field};

        if (!is_bool($value)) {
            throw new InvalidManifestException(
                sprintf(
                    'The Vite manifest entry "%s" in "%s" has an invalid "%s" value.',
                    $key,
                    $manifestPath,
                    $field,
                ),
            );
        }

        return $value;
    }

    /**
     * @return list<string>
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
                sprintf(
                    'The Vite manifest entry "%s" in "%s" has an invalid "%s" list.',
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
                    sprintf(
                        'The Vite manifest entry "%s" in "%s" contains an invalid "%s[%d]" value.',
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
                        sprintf(
                            'The Vite manifest entry "%s" in "%s" contains an invalid "%s[%d]" path.',
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

    private function optionalString(stdClass $chunk, string $field, string $key, string $manifestPath): string|null
    {
        if (!property_exists($chunk, $field)) {
            return null;
        }

        $value = $chunk->{$field};

        if (!is_string($value)) {
            throw new InvalidManifestException(
                sprintf(
                    'The Vite manifest entry "%s" in "%s" has an invalid "%s" value.',
                    $key,
                    $manifestPath,
                    $field,
                ),
            );
        }

        return $value;
    }

    private function parse(string $manifestPath, stdClass $decoded): Manifest
    {
        $chunks = [];

        foreach ((array) $decoded as $key => $value) {
            if (!is_string($key) || $key === '' || !$value instanceof stdClass) {
                throw new InvalidManifestException(
                    sprintf('The Vite manifest file "%s" contains an invalid entry.', $manifestPath),
                );
            }

            $file = $value->file ?? null;

            if (!is_string($file) || $file === '') {
                throw new InvalidManifestException(
                    sprintf('The Vite manifest entry "%s" in "%s" has no valid "file".', $key, $manifestPath),
                );
            }

            try {
                $file = Url::normalizeAssetPath($file, 'Vite manifest "file" value');
            } catch (ConfigurationException $exception) {
                throw new InvalidManifestException(
                    sprintf('The Vite manifest entry "%s" in "%s" has an invalid "file" path.', $key, $manifestPath),
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
                            sprintf(
                                'The Vite manifest entry "%s" in "%s" references missing "%s" chunk "%s".',
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
