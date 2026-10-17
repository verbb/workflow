<?php
declare(strict_types=1);

namespace Verbb\PhpCheck;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;

final class ProjectSymbols
{
    private array $prefixes = [];
    private array $psr4 = [];
    private array $psr0 = [];
    private array $classmap = [];
    private array $known = [];
    private array $parsed = [];

    public function __construct(private string $project, ?string $vendor, private ClassReferences $scanner)
    {
        $manifest = json_decode(file_get_contents($project . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (['autoload', 'autoload-dev'] as $section) {
            foreach ($manifest[$section]['psr-4'] ?? [] as $prefix => $paths) {
                $this->prefixes[] = $prefix;
                foreach ((array)$paths as $path) {
                    $this->psr4[$prefix][] = $project . '/' . $path;
                    if ($section === 'autoload') {
                        $this->indexPath($project . '/' . $path);
                    }
                }
            }
            foreach (array_merge($manifest[$section]['classmap'] ?? [], $manifest[$section]['files'] ?? []) as $path) {
                foreach (glob($project . '/' . $path) ?: [] as $match) {
                    if ($section === 'autoload') {
                        $this->indexPath($match);
                    }
                }
            }
        }
        if ($vendor !== null) {
            $vendor = realpath($vendor);
            if (!$vendor || !is_file($vendor . '/composer/autoload_psr4.php')) {
                throw new RuntimeException('The selected vendor directory has no Composer autoload maps.');
            }
            // Read generated maps only. Never include vendor/autoload.php or execute project classes.
            $map = require $vendor . '/composer/autoload_psr4.php';
            foreach ($map as $prefix => $paths) {
                $this->psr4[$prefix] = array_merge($this->psr4[$prefix] ?? [], $paths);
            }
            if (is_file($vendor . '/composer/autoload_namespaces.php')) {
                $this->psr0 = require $vendor . '/composer/autoload_namespaces.php';
            }
            if (is_file($vendor . '/composer/autoload_classmap.php')) {
                $this->classmap = require $vendor . '/composer/autoload_classmap.php';
            }
            // Composer, Craft and Yii register entry classes outside the ordinary class map.
            // Inspect Craft’s generated behavior template without booting or modifying Craft.
            foreach (['composer/ClassLoader.php', 'craftcms/cms/src/Craft.php', 'yiisoft/yii2/Yii.php', 'craftcms/cms/src/behaviors/CustomFieldBehavior.php.template'] as $file) {
                if (is_file($vendor . '/' . $file)) {
                    $this->parse($vendor . '/' . $file);
                }
            }
        }
    }

    public static function findProject(string $target): ?string
    {
        $path = realpath($target);
        if (!$path) {
            return null;
        }
        $dir = is_dir($path) ? $path : dirname($path);
        while ($dir !== dirname($dir)) {
            if (is_file($dir . '/composer.json')) {
                return $dir;
            }
            $dir = dirname($dir);
        }
        return null;
    }

    public static function phpFiles(string $path): array
    {
        if (is_file($path)) {
            if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                throw new RuntimeException('Not a PHP file: ' . $path);
            }
            return [$path];
        }
        if (!is_dir($path)) {
            throw new RuntimeException('Missing source directory: ' . $path);
        }
        $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, static function($file) {
            return !in_array($file->getFilename(), ['vendor', 'node_modules', '.git', '.cache'], true) && !$file->isLink();
        });
        $files = [];
        foreach (new RecursiveIteratorIterator($filter) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    public function isProjectClass(string $name): bool
    {
        foreach ($this->prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with(strtolower($name), strtolower($prefix))) {
                return true;
            }
        }
        return false;
    }

    public function exists(string $name): bool
    {
        if (isset($this->known[$name])) {
            return true;
        }
        // Internal PHP classes are already loaded and case-insensitive; PSR autoloaded classes are not.
        // Do not let the checker's own parser dependency satisfy a target's missing dependency.
        if (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)) {
            $class = new ReflectionClass($name);
            if ($class->isInternal()) {
                return true;
            }
        }
        $paths = isset($this->classmap[$name]) ? [$this->classmap[$name]] : [];
        foreach ($this->psr4 as $prefix => $directories) {
            if (str_starts_with($name, $prefix)) {
                foreach ($directories as $directory) {
                    $paths[] = rtrim($directory, '/') . '/' . str_replace('\\', '/', substr($name, strlen($prefix))) . '.php';
                }
            }
        }
        foreach ($this->psr0 as $prefix => $directories) {
            if (str_starts_with($name, $prefix)) {
                $separator = strrpos($name, '\\');
                $relative = $separator === false
                    ? str_replace('_', '/', $name)
                    : str_replace('\\', '/', substr($name, 0, $separator + 1)) . str_replace('_', '/', substr($name, $separator + 1));
                foreach ($directories as $directory) {
                    $paths[] = rtrim($directory, '/') . '/' . $relative . '.php';
                }
            }
        }
        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            $this->parse($path);
            if (isset($this->known[$name])) {
                return true;
            }
        }
        return false;
    }

    private function indexPath(string $path): void
    {
        foreach (self::phpFiles($path) as $file) {
            $this->parse($file);
        }
    }

    private function parse(string $file): void
    {
        $file = realpath($file);
        if (isset($this->parsed[$file])) {
            return;
        }
        $this->parsed[$file] = true;
        try {
            $result = $this->scanner->inspect(file_get_contents($file));
        } catch (\Throwable $error) {
            throw new RuntimeException($file . ': ' . $error->getMessage(), 0, $error);
        }
        foreach ($result['declarations'] as $name) {
            $this->known[$name] = true;
        }
    }
}
