<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use InvalidArgumentException;

/**
 * Reports invalid Vite integration configuration.
 */
class ConfigurationException extends InvalidArgumentException implements ViteException {}
