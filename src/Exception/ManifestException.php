<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use RuntimeException;

/**
 * Base exception for Vite manifest loading and validation failures.
 */
class ManifestException extends RuntimeException implements ViteException {}
