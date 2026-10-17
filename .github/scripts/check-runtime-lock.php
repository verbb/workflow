<?php

declare(strict_types=1);

$root = $argv[1] ?? dirname(__DIR__, 2);
$package = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$lock = json_decode(file_get_contents($root . '/tests/runtime/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
foreach ($lock['packages'] as $dependency) {
    if ($dependency['name'] !== $package['name']) {
        continue;
    }

    $expected = $package['require'] ?? [];
    $locked = $dependency['require'] ?? [];
    ksort($expected);
    ksort($locked);
    if ($expected !== $locked) {
        throw new RuntimeException('The integration test lock contains outdated plugin requirements. Refresh tests/runtime/composer.lock before running CI.');
    }

    echo "Integration test lock matches the plugin requirements.\n";
    exit(0);
}

throw new RuntimeException('The integration test lock does not contain this plugin.');
