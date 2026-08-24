<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

use PHPForge\Vite\Exception\Message;

/**
 * Data provider for {@see \PHPForge\Vite\Tests\ManifestLoaderTest} test cases.
 */
final class ManifestLoaderProvider
{
    /**
     * @return iterable<string, array{string, Message, list<int|string|null>}>
     */
    public static function invalidManifests(): iterable
    {
        yield 'root list' => [
            '[]',
            Message::MANIFEST_ROOT_INVALID,
            [null],
        ];
        yield 'scalar root' => [
            '"hello"',
            Message::MANIFEST_ROOT_INVALID,
            [null],
        ];
        yield 'chunk list' => [
            '{"entry.js":[]}',
            Message::MANIFEST_ENTRY_INVALID,
            [null],
        ];
        yield 'missing file' => [
            '{"entry.js":{"isEntry":true}}',
            Message::MANIFEST_ENTRY_FILE_INVALID,
            ['entry.js', null],
        ];
        yield 'non-string file' => [
            '{"entry.js":{"file":123}}',
            Message::MANIFEST_ENTRY_FILE_INVALID,
            ['entry.js', null],
        ];
        yield 'absolute file URL' => [
            '{"entry.js":{"file":"https://cdn.test/app.js"}}',
            Message::MANIFEST_ENTRY_FILE_PATH_INVALID,
            ['entry.js', null],
        ];
        yield 'css not a list' => [
            '{"entry.js":{"file":"app.js","css":"app.css"}}',
            Message::MANIFEST_FIELD_LIST_INVALID,
            ['entry.js', null, 'css'],
        ];
        yield 'css item not string' => [
            '{"entry.js":{"file":"app.js","css":[123]}}',
            Message::MANIFEST_LIST_ITEM_INVALID,
            ['entry.js', null, 'css', 0],
        ];
        yield 'invalid css path' => [
            '{"entry.js":{"file":"app.js","css":["../app.css"]}}',
            Message::MANIFEST_LIST_ITEM_PATH_INVALID,
            ['entry.js', null, 'css', 0],
        ];
        yield 'imports not a list' => [
            '{"entry.js":{"file":"app.js","imports":{}}}',
            Message::MANIFEST_FIELD_LIST_INVALID,
            ['entry.js', null, 'imports'],
        ];
        yield 'empty import' => [
            '{"entry.js":{"file":"app.js","imports":[""]}}',
            Message::MANIFEST_LIST_ITEM_INVALID,
            ['entry.js', null, 'imports', 0],
        ];
        yield 'isEntry not boolean' => [
            '{"entry.js":{"file":"app.js","isEntry":1}}',
            Message::MANIFEST_FIELD_VALUE_INVALID,
            ['entry.js', null, 'isEntry'],
        ];
        yield 'src not string' => [
            '{"entry.js":{"file":"app.js","src":[]}}',
            Message::MANIFEST_FIELD_VALUE_INVALID,
            ['entry.js', null, 'src'],
        ];
        yield 'missing static reference' => [
            '{"entry.js":{"file":"app.js","imports":["_missing.js"]}}',
            Message::MANIFEST_REFERENCE_MISSING,
            ['entry.js', null, 'imports', '_missing.js'],
        ];
        yield 'missing dynamic reference' => [
            '{"entry.js":{"file":"app.js","dynamicImports":["missing.js"]}}',
            Message::MANIFEST_REFERENCE_MISSING,
            ['entry.js', null, 'dynamicImports', 'missing.js'],
        ];
    }
}
