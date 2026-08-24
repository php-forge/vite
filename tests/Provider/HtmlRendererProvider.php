<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Provider;

use PHPForge\Vite\Exception\Message;
use stdClass;

/**
 * Data provider for {@see \PHPForge\Vite\Tests\HtmlRendererTest} test cases.
 */
final class HtmlRendererProvider
{
    /**
     * @return iterable<string, array{array<mixed, mixed>, Message, list<int|string>}>
     */
    public static function unsafeAttributes(): iterable
    {
        yield 'reserved' => [
            ['src' => '/override.js'],
            Message::HTML_ATTRIBUTE_RESERVED,
            ['src'],
        ];
        yield 'reserved case insensitive' => [
            ['SRC' => '/override.js'],
            Message::HTML_ATTRIBUTE_RESERVED,
            ['SRC'],
        ];
        yield 'event handler' => [
            ['onload' => 'alert(1)'],
            Message::HTML_ATTRIBUTE_RESERVED,
            ['onload'],
        ];
        yield 'inline style' => [
            ['style' => 'display:none'],
            Message::HTML_ATTRIBUTE_RESERVED,
            ['style'],
        ];
        yield 'invalid name' => [
            ['bad name' => 'value'],
            Message::HTML_ATTRIBUTE_NAME_INVALID,
            [],
        ];
        yield 'unsupported colon name' => [
            ['data:value' => 'value'],
            Message::HTML_ATTRIBUTE_NAME_INVALID,
            [],
        ];
        yield 'unsupported dot name' => [
            ['data.value' => 'value'],
            Message::HTML_ATTRIBUTE_NAME_INVALID,
            [],
        ];
        yield 'non-finite float' => [
            ['data-value' => INF],
            Message::HTML_ATTRIBUTE_VALUE_INVALID,
            ['data-value'],
        ];
        yield 'invalid value' => [
            ['data-value' => new stdClass()],
            Message::HTML_ATTRIBUTE_VALUE_INVALID,
            ['data-value'],
        ];
    }
}
