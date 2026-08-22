<?php

declare(strict_types=1);

namespace PHPForge\Vite\Tests;

use PHPForge\Vite\Manifest\ManifestChunk;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ManifestChunk} asset type detection.
 */
#[Group('manifest')]
final class ManifestChunkTest extends TestCase
{
    public function testCssDetectionIsCaseInsensitive(): void
    {
        self::assertTrue(
            (new ManifestChunk('app.css', 'assets/app.CSS'))->isCss(),
            'An uppercase CSS extension must be recognized.',
        );
    }
}
