<?php

declare(strict_types=1);

if (PHP_VERSION !== '8.2.0') {
    throw new RuntimeException('The minimum PHP check requires PHP 8.2.0; got ' . PHP_VERSION);
}

require dirname(__DIR__, 2) . '/.github/scripts/prepare-check-dependencies.php';
