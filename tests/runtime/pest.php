<?php

require dirname(__DIR__, 2) . '/.github/scripts/runtime-preflight.php';

// Pest derives its root from vendor/, which belongs to the generated Craft app.
// Point its discovery at the checkout so Pest.php, helpers and datasets load too.
require_once __DIR__ . '/bootstrap.php';
require dirname(__DIR__, 2) . '/.github/scripts/pest-exit.php';
$_SERVER['argv'][] = '--test-directory=../../../tests';
require CRAFT_VENDOR_PATH . '/pestphp/pest/bin/pest';
