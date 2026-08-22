<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Exception\{
    ConfigurationException,
    InvalidManifestException,
    ManifestNotFoundException,
    ManifestReadException,
};
use PHPForge\Vite\Manifest\ManifestLoader;
use PHPForge\Vite\Tests\Provider\ManifestLoaderProvider;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;

use function chmod;
use function clearstatcache;
use function file_put_contents;
use function is_readable;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Unit tests for {@see ManifestLoader} validation, parsing, caching, and filesystem failures.
 *
 * {@see ManifestLoaderProvider} for test case data providers.
 */
#[Group('manifest')]
final class ManifestLoaderTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    public function testLoaderAllowsUnknownFieldsForForwardCompatibility(): void
    {
        $path = $this->temporaryManifest(
            '{"entry.js":{"file":"assets/entry.js","isEntry":true,"futureField":{"enabled":true}}}',
        );

        self::assertNotNull(
            (new ManifestLoader())->load($path)->get('entry.js'),
            'Unknown fields must not discard a valid chunk.',
        );
    }

    public function testLoaderCachesUntilFileChangesOrCacheIsCleared(): void
    {
        $path = $this->temporaryManifest(
            '{"entry.js":{"file":"assets/entry.js","isEntry":true}}',
        );

        $loader = new ManifestLoader();

        $first = $loader->load($path);
        $second = $loader->load($path);

        self::assertSame(
            $first,
            $second,
            'Unchanged files must reuse the cached manifest.',
        );

        file_put_contents(
            $path,
            '{"changed.js":{"file":"assets/changed-longer-name.js","isEntry":true}}',
        );
        clearstatcache(true, $path);

        $changed = $loader->load($path);

        self::assertNotSame(
            $first,
            $changed,
            'Changed files must produce a new manifest instance.',
        );
        self::assertNotNull(
            $changed->get('changed.js'),
            'The updated chunk must be available.',
        );

        $loader->clear($path);

        self::assertNotSame(
            $changed,
            $loader->load($path),
            'Clearing the cache must force a new instance.',
        );
    }

    public function testLoaderParsesCurrentOfficialManifestFields(): void
    {
        $manifest = (new ManifestLoader())->load(__DIR__ . '/Fixture/manifest.json');

        $entry = $manifest->get('views/bar.js');
        $shared = $manifest->get('_shared-B7PI925R.js');

        self::assertNotNull(
            $entry,
            'The entry chunk must be present.',
        );
        self::assertSame(
            'assets/bar-gkvgaI9m.js',
            $entry->file,
            'The emitted file path must be parsed.',
        );
        self::assertSame(
            'views/bar.js',
            $entry->src,
            'The source path must be parsed.',
        );
        self::assertTrue($entry->isEntry, 'The entry flag must be `true`.');
        self::assertFalse($entry->isDynamicEntry, 'The dynamic entry flag must default to `false`.');
        self::assertSame(
            ['_shared-B7PI925R.js'],
            $entry->imports,
            'Static imports must be parsed.',
        );
        self::assertSame(
            ['baz.js'],
            $entry->dynamicImports,
            'Dynamic imports must be parsed.',
        );
        self::assertNotNull(
            $shared,
            'The imported chunk must be present.',
        );
        self::assertSame(
            ['assets/shared-ChJ_j-JJ.css'],
            $shared->css,
            'CSS assets must be parsed.',
        );
        self::assertSame(
            ['assets/logo-BuPIv-2h.svg'],
            $shared->assets,
            'Static assets must be parsed.',
        );
    }

    public function testThrowConfigurationExceptionForNonAbsoluteCachePath(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'absolute filesystem path',
        );

        (new ManifestLoader())->clear('@webroot/build/.vite/manifest.json');
    }

    #[DataProviderExternal(ManifestLoaderProvider::class, 'invalidManifests')]
    public function testThrowInvalidManifestExceptionForInvalidManifestStructure(string $json, string $message): void
    {
        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage(
            $message,
        );

        (new ManifestLoader())->load($this->temporaryManifest($json));
    }

    public function testThrowInvalidManifestExceptionForMalformedJson(): void
    {
        $this->expectException(InvalidManifestException::class);
        $this->expectExceptionMessage(
            'Unable to decode',
        );

        (new ManifestLoader())->load(__DIR__ . '/Fixture/invalid-manifest.json');
    }

    public function testThrowManifestNotFoundExceptionWhenManifestIsMissing(): void
    {
        $this->expectException(ManifestNotFoundException::class);
        $this->expectExceptionMessage(
            'does not exist',
        );

        (new ManifestLoader())->load(sys_get_temp_dir() . '/php-forge-vite-missing-manifest.json');
    }

    public function testThrowManifestReadExceptionWhenManifestIsUnreadable(): void
    {
        $path = $this->temporaryManifest('{}');

        chmod($path, 0o000);
        clearstatcache(true, $path);

        try {
            if (is_readable($path)) {
                self::markTestSkipped(
                    'The current filesystem user can still read files with mode 000.',
                );
            }

            $this->expectException(ManifestReadException::class);
            $this->expectExceptionMessage(
                'Unable to read',
            );

            (new ManifestLoader())->load($path);
        } finally {
            chmod($path, 0o600);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_readable($path)) {
                unlink($path);
            }
        }
    }

    private function temporaryManifest(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'php-forge-vite-');

        self::assertIsString(
            $path,
            'A temporary manifest path must be created.',
        );
        self::assertNotFalse(
            file_put_contents($path, $content),
            'The temporary manifest must be writable.',
        );

        $this->temporaryFiles[] = $path;

        return $path;
    }
}
