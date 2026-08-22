<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

use stdClass;

/**
 * Data provider for {@see HtmlRendererTest} test cases.
 */
final class HtmlRendererProvider
{
    /**
     * @return iterable<string, array{array<mixed, mixed>, string}>
     */
    public static function unsafeAttributes(): iterable
    {
        yield 'reserved' => [['src' => '/override.js'], 'reserved or unsafe'];
        yield 'reserved case insensitive' => [['SRC' => '/override.js'], 'reserved or unsafe'];
        yield 'event handler' => [['onload' => 'alert(1)'], 'reserved or unsafe'];
        yield 'inline style' => [['style' => 'display:none'], 'reserved or unsafe'];
        yield 'invalid name' => [['bad name' => 'value'], 'safe HTML name syntax'];
        yield 'unsupported colon name' => [['data:value' => 'value'], 'safe HTML name syntax'];
        yield 'unsupported dot name' => [['data.value' => 'value'], 'safe HTML name syntax'];
        yield 'non-finite float' => [['data-value' => INF], 'unsupported value'];
        yield 'invalid value' => [['data-value' => new stdClass()], 'unsupported value'];
    }
}
