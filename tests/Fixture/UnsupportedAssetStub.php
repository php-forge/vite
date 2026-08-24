<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests\Fixture;

use PHPForge\Vite\Asset\AssetInterface;

/**
 * Asset implementing the marker interface without being one of the four supported types.
 *
 * Exercises the guard that rejects third-party {@see AssetInterface} implementations.
 */
final class UnsupportedAssetStub implements AssetInterface {}
