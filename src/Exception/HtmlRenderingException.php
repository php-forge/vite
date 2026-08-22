<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use InvalidArgumentException;

final class HtmlRenderingException extends InvalidArgumentException implements ViteException {}
