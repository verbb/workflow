#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/ClassReferences.php';
require dirname(__DIR__) . '/src/ProjectSymbols.php';

use Verbb\PhpCheck\ClassReferences;
use Verbb\PhpCheck\ProjectSymbols;

try {
    $options = [];
    $targets = [];
    for ($i = 1; $i < $argc; $i++) {
        if ($argv[$i] === '--help') {
            echo "Usage: php-check [--project DIR] [--vendor DIR] [--optional FILE] FILE_OR_DIR ...\n";
            echo "Checks PHP syntax and static class references without executing project code.\n";
            exit(0);
        }
        if (str_starts_with($argv[$i], '--')) {
            $key = substr($argv[$i], 2);
            if (!in_array($key, ['project', 'vendor', 'optional'], true) || !isset($argv[$i + 1])) {
                throw new RuntimeException('Unknown or incomplete option: ' . $argv[$i]);
            }
            $options[$key] = $argv[++$i];
        } else {
            $targets[] = $argv[$i];
        }
    }
    if (!$targets) {
        throw new RuntimeException('Supply PHP files or source directories to check.');
    }
    $project = isset($options['project']) ? realpath($options['project']) : ProjectSymbols::findProject($targets[0]);
    if (!$project || !is_file($project . '/composer.json')) {
        throw new RuntimeException('Cannot locate composer.json. Supply --project DIR.');
    }
    $vendor = $options['vendor'] ?? null;
    if ($vendor === null) {
        foreach ([$project . '/vendor', $project . '/.cache/verbb-tests/app/vendor', dirname($project) . '/craft/vendor'] as $candidate) {
            if (is_file($candidate . '/composer/autoload_psr4.php')) {
                $vendor = $candidate;
                break;
            }
        }
    }
    $scanner = new ClassReferences();
    $symbols = new ProjectSymbols($project, $vendor, $scanner);
    $sources = [];
    foreach ($targets as $target) {
        $path = realpath($target);
        if (!$path || !str_starts_with($path, $project . '/')) {
            throw new RuntimeException('Target must exist inside the selected project: ' . $target);
        }
        foreach (ProjectSymbols::phpFiles($path) as $file) {
            $sources[$file] = file_get_contents($file);
        }
    }
    if (!$sources) {
        throw new RuntimeException('No PHP files found; nothing was checked.');
    }
    // Lint with the executing PHP runtime too: an AST alone accepts some compile-time errors.
    $batchSize = PHP_VERSION_ID >= 80300 ? 100 : 1;
    foreach (array_chunk(array_keys($sources), $batchSize) as $batch) {
        $process = proc_open([PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=stderr', '-d', 'log_errors=0', '-l', ...$batch], [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not run PHP syntax lint.');
        }
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        if (proc_close($process) !== 0 || preg_match('/(?:^|\n)(?:PHP )?Warning:/', $output)) {
            throw new RuntimeException(trim($output));
        }
    }
    $optional = [];
    if (isset($options['optional'])) {
        $path = realpath($options['optional']);
        if (!$path) {
            throw new RuntimeException('Optional dependency inventory does not exist.');
        }
        $optional = str_ends_with($path, '.json')
            ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)
            : require $path;
        if (!is_array($optional)) {
            throw new RuntimeException('The optional inventory must map exact class names to package/reason strings.');
        }
        foreach ($optional as $class => $reason) {
            if (!is_string($class) || str_contains($class, '*') || !is_string($reason) || trim($reason) === '' || $symbols->isProjectClass($class)) {
                throw new RuntimeException('Invalid optional dependency exemption: ' . (string)$class);
            }
        }
    }
    $errors = $scanner->unresolved($sources, $symbols->exists(...), $optional);
    foreach ($errors as $error) {
        fwrite(STDERR, $error . "\n");
    }
    if ($errors) {
        fwrite(STDERR, count($errors) . " unresolved class reference(s). Missing dependencies are not a passing check.\n");
        exit(1);
    }
    echo 'PHP syntax and class resolution passed for ' . count($sources) . " file(s).\n";
    if ($optional) {
        echo 'Optional external symbols are explicitly inventoried; their APIs are not validated by this check.' . "\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
