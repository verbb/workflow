<?php

declare(strict_types=1);

// Use the integration suite's retained dependency graph for the static gate too.
$root = dirname(__DIR__, 2);
$package = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$manifest = [
    'name' => 'verbb/plugin-test-app',
    'type' => 'project',
    'require' => array_merge($package['require-dev'] ?? [], [$package['name'] => '*']),
    'repositories' => [['type' => 'path', 'url' => '../../..', 'options' => ['symlink' => true, 'reference' => 'config']]],
    'minimum-stability' => 'dev',
    'prefer-stable' => true,
    'autoload-dev' => ['psr-4' => ['Tests\\' => '../../../tests/']],
    'config' => [
        'platform' => ['php' => '8.2.0', 'php-64bit' => '8.2.0'],
        'allow-plugins' => ['craftcms/plugin-installer' => true, 'pestphp/pest-plugin' => true, 'yiisoft/yii2-composer' => true],
    ],
];
$directory = $root . '/.cache/verbb-tests/app';
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create the isolated dependency directory.');
}
file_put_contents($directory . '/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
if (!copy($root . '/tests/runtime/composer.lock', $directory . '/composer.lock')) {
    throw new RuntimeException('The retained integration dependency lock is required.');
}
