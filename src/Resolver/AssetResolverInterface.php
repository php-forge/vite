<?php

declare(strict_types=1);

namespace PHPForge\Vite\Resolver;

use PHPForge\Vite\Asset\AssetCollection;
use PHPForge\Vite\Exception\ViteException;

/**
 * Resolves normalized Vite entrypoints into framework-neutral assets.
 */
interface AssetResolverInterface
{
    /**
     * Resolves the supplied entrypoints into the assets a page must load.
     *
     * Implementations receive entrypoints that are already normalized and non-empty, and return them in the order
     * the consuming page is expected to emit.
     *
     * @param list<string> $entrypoints Normalized relative entrypoint paths.
     *
     * @throws ViteException if an entrypoint cannot be resolved, or if the underlying source is invalid.
     *
     * @return AssetCollection Deduplicated assets in emission order.
     */
    public function resolve(array $entrypoints): AssetCollection;
}
