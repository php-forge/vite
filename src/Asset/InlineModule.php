<?php

declare(strict_types=1);

namespace PHPForge\Vite\Asset;

use PHPForge\Vite\Exception\ConfigurationException;

use function trim;

final readonly class InlineModule implements AssetInterface
{
    public function __construct(public string $source)
    {
        if (trim($source) === '') {
            throw new ConfigurationException(
                'Inline module source must not be empty.',
            );
        }
    }
}
