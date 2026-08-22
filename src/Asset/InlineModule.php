<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use PHPForge\Vite\Exception\{ConfigurationException, Message};

use function trim;

final readonly class InlineModule implements AssetInterface
{
    public function __construct(public string $source)
    {
        if (trim($source) === '') {
            throw new ConfigurationException(
                Message::INLINE_MODULE_SOURCE_EMPTY->getMessage(),
            );
        }
    }
}
