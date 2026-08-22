<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

/**
 * Data provider for {@see ManifestLoaderTest} test cases.
 */
final class ManifestLoaderProvider
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidManifests(): iterable
    {
        yield 'root list' => ['[]', 'must contain a JSON object'];
        yield 'scalar root' => ['"hello"', 'must contain a JSON object'];
        yield 'chunk list' => ['{"entry.js":[]}', 'invalid entry'];
        yield 'missing file' => ['{"entry.js":{"isEntry":true}}', 'no valid "file"'];
        yield 'non-string file' => ['{"entry.js":{"file":123}}', 'no valid "file"'];
        yield 'absolute file URL' => ['{"entry.js":{"file":"https://cdn.test/app.js"}}', 'invalid "file" path'];
        yield 'css not a list' => ['{"entry.js":{"file":"app.js","css":"app.css"}}', 'invalid "css" list'];
        yield 'css item not string' => ['{"entry.js":{"file":"app.js","css":[123]}}', 'invalid "css[0]"'];
        yield 'imports not a list' => ['{"entry.js":{"file":"app.js","imports":{}}}', 'invalid "imports" list'];
        yield 'empty import' => ['{"entry.js":{"file":"app.js","imports":[""]}}', 'invalid "imports[0]"'];
        yield 'isEntry not boolean' => ['{"entry.js":{"file":"app.js","isEntry":1}}', 'invalid "isEntry"'];
        yield 'src not string' => ['{"entry.js":{"file":"app.js","src":[]}}', 'invalid "src"'];
        yield 'missing static reference' => [
            '{"entry.js":{"file":"app.js","imports":["_missing.js"]}}',
            'references missing "imports" chunk',
        ];
        yield 'missing dynamic reference' => [
            '{"entry.js":{"file":"app.js","dynamicImports":["missing.js"]}}',
            'references missing "dynamicImports" chunk',
        ];
    }
}
