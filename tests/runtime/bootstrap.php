<?php

declare(strict_types=1);

// Never load the developer's .env or .env.testing into this disposable app.
$pluginRoot = dirname(__DIR__, 2);
$runtimeRoot = $pluginRoot . '/.cache/verbb-tests';
$owner = json_decode((string)@file_get_contents($runtimeRoot . '/owner.json'), true);
if (getenv('IS_DDEV_PROJECT') !== 'true' || !$owner || ($owner['project'] ?? null) !== getenv('DDEV_SITENAME') || ($owner['source'] ?? null) !== realpath($pluginRoot)) {
    throw new RuntimeException('Tests require an owned DDEV runtime. Run ddev test from the plugin checkout.');
}
foreach (['.cache', '.cache/verbb-tests', '.cache/verbb-tests/app'] as $relative) {
    if (is_link($pluginRoot . '/' . $relative)) {
        throw new RuntimeException('Refusing a symlinked test runtime.');
    }
}
$appRoot = $runtimeRoot . '/app';
foreach (getenv() as $key => $value) {
    if (str_starts_with($key, 'CRAFT_') || in_array($key, ['DOTENV_FILE', 'ENVIRONMENT', 'PRIMARY_SITE_URL'], true)) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
foreach ([
    'ENVIRONMENT' => 'testing', 'CRAFT_ENVIRONMENT' => 'testing',
    'CRAFT_DB_DRIVER' => 'mysql', 'CRAFT_DB_SERVER' => 'db', 'CRAFT_DB_PORT' => '3306',
    'CRAFT_DB_DATABASE' => 'db', 'CRAFT_DB_USER' => 'db', 'CRAFT_DB_PASSWORD' => 'db',
    'CRAFT_SECURITY_KEY' => 'verbb-disposable-test-runtime-not-for-production',
    'CRAFT_APP_ID' => 'VerbbTests-' . $owner['project'],
    'PRIMARY_SITE_URL' => 'https://' . $owner['project'] . '.ddev.site',
] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
define('CRAFT_BASE_PATH', $appRoot);
define('CRAFT_VENDOR_PATH', $appRoot . '/vendor');
define('CRAFT_CONFIG_PATH', $appRoot . '/config');
define('CRAFT_STORAGE_PATH', $appRoot . '/storage');
define('CRAFT_RUNTIME_PATH', CRAFT_STORAGE_PATH . '/runtime');
define('CRAFT_WEB_ROOT', $appRoot . '/web');
require_once CRAFT_VENDOR_PATH . '/autoload.php';
