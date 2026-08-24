<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use InvalidArgumentException;

/**
 * Reports unsafe or unsupported HTML rendering options.
 */
final class HtmlRenderingException extends InvalidArgumentException implements ViteException {}
