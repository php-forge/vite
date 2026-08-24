<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

/**
 * Reports an entrypoint missing from a loaded Vite manifest.
 */
final class EntrypointNotFoundException extends ManifestException {}
