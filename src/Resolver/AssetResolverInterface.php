<?php

declare(strict_types=1);

namespace PHPForge\Vite\Resolver;

use PHPForge\Vite\Asset\AssetCollection;

interface AssetResolverInterface
{
    /**
     * @param list<string> $entrypoints
     */
    public function resolve(array $entrypoints): AssetCollection;
}
