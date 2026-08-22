<?php

declare(strict_types=1);

namespace PHPForge\Vite\Exception;

use InvalidArgumentException;

class ConfigurationException extends InvalidArgumentException implements ViteException {}
