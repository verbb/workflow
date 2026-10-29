<?php

declare(strict_types=1);

// Pest 3 can return success before evaluating fail-on-risky/skipped/incomplete.
// Retain PHPUnit's own exit policy rather than reimplementing its issue rules.
register_shutdown_function(static function(): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    if (!class_exists(\PHPUnit\TextUI\Configuration\Registry::class, false)) {
        return;
    }
    try {
        $configuration = \PHPUnit\TextUI\Configuration\Registry::get();
    } catch (\AssertionError|\TypeError) {
        // Argument parsing failed before a test configuration was created.
        return;
    }
    $code = (new \PHPUnit\TextUI\ShellExitCodeCalculator())->calculate(
        $configuration,
        \PHPUnit\TestRunner\TestResult\Facade::result(),
    );
    if ($code !== 0) {
        exit($code);
    }
});
