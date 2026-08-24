<?php

declare(strict_types=1);

/**
 * Corrects optional argument defaults in the internal-mocker function metadata.
 */
$stubs = require __DIR__ . '/../../vendor/xepozz/internal-mocker/src/stubs.php';

if (!is_array($stubs)) {
    throw new RuntimeException('Internal mocker stubs must be an array.');
}

$stubs['file_get_contents'] = [
    'signatureArguments' => 'string $filename, bool $use_include_path = false, $context = null, int $offset = 0, int|null $length = null',
    'arguments' => '$filename, $use_include_path, $context, $offset, $length',
];

return $stubs;
