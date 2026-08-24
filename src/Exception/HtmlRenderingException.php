<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use InvalidArgumentException;

/**
 * Reports a failure encountered while rendering neutral Vite assets as HTML.
 *
 * Raised for an unsafe or unsupported render option, for an asset implementation the renderer cannot map to a tag,
 * and when inline module source cannot be neutralized.
 */
final class HtmlRenderingException extends InvalidArgumentException implements ViteException {}
