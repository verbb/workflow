<?php

declare(strict_types=1);

// Run before Pest, and after PHPUnit applies its configuration in the bootstrap.
$limit = ini_get('memory_limit');
if ($limit !== '-1' && ini_parse_quantity($limit) < 1024 * 1024 * 1024) {
    if (ini_set('memory_limit', '1G') === false) {
        throw new RuntimeException('The PHP test runtime requires at least 1 GB of memory.');
    }
}
$metadata = [
    'php' => PHP_VERSION,
    'binary' => PHP_BINARY,
    'memoryLimit' => ini_get('memory_limit'),
    'extensions' => get_loaded_extensions(),
];
$directory = dirname(__DIR__, 2) . '/.cache/verbb-tests';
if (is_dir($directory)) {
    file_put_contents($directory . '/interpreter.json', json_encode($metadata, JSON_PRETTY_PRINT) . "\n");
}
