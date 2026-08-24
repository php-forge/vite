<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use PHPForge\Vite\Exception\{ConfigurationException, Message};

use function trim;

/**
 * Represents validated inline JavaScript module source.
 */
final readonly class InlineModule implements AssetInterface
{
    /**
     * @param string $source JavaScript module source rendered inside a `<script type="module">` element.
     *
     * @throws ConfigurationException if the source is empty or contains only whitespace.
     */
    public function __construct(public string $source)
    {
        if (trim($source) === '') {
            throw new ConfigurationException(
                Message::INLINE_MODULE_SOURCE_EMPTY->getMessage(),
            );
        }
    }
}
