<?php

declare(strict_types=1);

// Resolve this job's dependencies separately from development and application locks.
$root = dirname(__DIR__, 2);
$package = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
if (PHP_VERSION !== '8.2.0') {
    throw new RuntimeException('The minimum PHP check requires PHP 8.2.0; got ' . PHP_VERSION);
}
$repositories = $package['repositories'] ?? [];
foreach ($repositories as &$repository) {
    if (($repository['type'] ?? '') === 'path') {
        $path = realpath($root . '/' . $repository['url']);
        if (!$path) {
            throw new RuntimeException('Required local dependency is unavailable: ' . $repository['url'] . '. Publish the dependency or provide its source before running this check.');
        }
        $repository['url'] = $path;
    }
}
unset($repository);
$repositories[] = ['type' => 'composer', 'url' => 'https://asset-packagist.org'];
$manifest = [
    'name' => 'verbb/php-check-dependencies',
    'type' => 'project',
    'require' => array_merge($package['require'] ?? [], $package['require-dev'] ?? []),
    'repositories' => $repositories,
    'minimum-stability' => $package['minimum-stability'] ?? 'stable',
    'prefer-stable' => true,
    'config' => ['platform' => ['php' => '8.2.0'], 'allow-plugins' => false],
];
$directory = $root . '/.cache/php-check';
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create the isolated dependency directory.');
}
file_put_contents($directory . '/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo 'Prepared PHP ' . PHP_VERSION . " dependency resolution.\n";
