<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$temp = sys_get_temp_dir() . '/php-check-tests-' . bin2hex(random_bytes(6));
mkdir($temp . '/src', 0777, true);
mkdir($temp . '/vendor/composer', 0777, true);
mkdir($temp . '/vendor/example', 0777, true);
file_put_contents($temp . '/composer.json', json_encode(['autoload' => ['psr-4' => ['Example\\' => 'src/']]]));
file_put_contents($temp . '/src/Item.php', '<?php namespace Example; class Item {}');
file_put_contents($temp . '/vendor/autoload.php', '<?php throw new Exception("Target autoload must not execute");');
file_put_contents($temp . '/vendor/example/Dependency.php', '<?php namespace Vendor; class Dependency {}');
file_put_contents($temp . '/vendor/example/Wrong.php', '<?php namespace Vendor; class Different {}');
file_put_contents($temp . '/vendor/composer/autoload_psr4.php', '<?php return ["Vendor\\\\" => [dirname(__DIR__) . "/example"]];');
file_put_contents($temp . '/vendor/composer/autoload_classmap.php', '<?php return [];');
mkdir($temp . '/vendor/example/Legacy');
file_put_contents($temp . '/vendor/example/Legacy/Thing.php', '<?php class Legacy_Thing {}');
file_put_contents($temp . '/vendor/composer/autoload_namespaces.php', '<?php return ["Legacy_" => [dirname(__DIR__) . "/example"]];');
file_put_contents($temp . '/vendor/composer/ClassLoader.php', '<?php namespace Composer\\Autoload; class ClassLoader {}');
$assertions = 0;
$run = static function(string $name, string $source, bool $pass, array $expected = [], array $options = []) use ($root, $temp, &$assertions): void {
    file_put_contents($temp . '/src/Probe.php', $source);
    $process = proc_open([PHP_BINARY, $root . '/bin/check.php', '--project', $temp, ...$options, $temp . '/src/Probe.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($process);
    if (($code === 0) !== $pass) {
        throw new RuntimeException($name . ': unexpected exit ' . $code . "\n" . $out);
    }
    foreach ($expected as $needle) {
        if (!str_contains($out, $needle)) {
            throw new RuntimeException($name . ': missing diagnostic ' . $needle . "\n" . $out);
        }
    }
    $assertions++;
};
try {
    $run('valid aliases and dependency', '<?php namespace Example; use Vendor\Dependency as Imported; use Example as Group; new Imported(); new Group\Item(); new \DateTime();', true);
    $run('built-in names are case-insensitive', '<?php new \\dateTIME();', true);
    $run('Composer bootstrap class', '<?php new \\Composer\\Autoload\\ClassLoader();', true);
    $run('PSR-0 dependency', '<?php new \\Legacy_Thing();', true);
    $run('missing import', '<?php namespace Example; Dependency::run();', false, ['Example\\Dependency']);
    $run('Carbon missing import', '<?php namespace Example; Carbon::now();', false, ['Example\\Carbon']);
    $run('PHP 8.3 typed constant', '<?php class Probe { public const string NAME = \'probe\'; }', PHP_VERSION_ID >= 80300);
    $run('wrong alias', '<?php namespace Example; use Vendor\Dependency as Imported; new Dependency();', false, ['Example\\Dependency']);
    $run('wrong namespace', '<?php new \Other\Dependency();', false, ['Other\\Dependency']);
    $run('case-sensitive source', '<?php namespace Example; new item();', false, ['Example\\item']);
    $run('case-sensitive dependency', '<?php new \Vendor\dependency();', false, ['Vendor\\dependency']);
    $run('file without matching declaration', '<?php new \Vendor\Wrong();', false, ['Vendor\\Wrong']);
    $run('syntax error', '<?php class Broken {', false, ['Syntax error']);
    $run('compile-time redundant global import', '<?php use DateTime; new DateTime();', false, ['has no effect']);
    $run('compile-time duplicate method', '<?php class Probe { public function run() {} public function run() {} }', false, ['Cannot redeclare']);
    $run('function and constant imports', '<?php namespace Example; use function Vendor\run; use const Vendor\FLAG; run(FLAG);', true);
    $run('self static parent', '<?php namespace Example; class Child extends Item { public function run(): self { parent::run(); return new static(); }}', true);
    $run('declared alias', '<?php namespace Example; class_alias(Item::class, Legacy::class); new Legacy();', true);
    $run('all class syntax', '<?php namespace Example; #[MissingAttribute] class Probe extends MissingBase implements MissingInterface { use MissingTrait; public MissingType $value; public function run(MissingParam $x): MissingReturn { try { $x instanceof MissingCheck; MissingStatic::$value; $x = MissingLiteral::class; } catch (MissingException $e) {} }}', false, ['MissingAttribute', 'MissingBase', 'MissingInterface', 'MissingTrait', 'MissingType', 'MissingParam', 'MissingReturn', 'MissingCheck', 'MissingStatic', 'MissingLiteral', 'MissingException']);
    $run('tool dependency cannot mask project dependency', '<?php new \PhpParser\ParserFactory();', false, ['PhpParser\\ParserFactory']);
    file_put_contents($temp . '/optional.json', json_encode(['Optional\\Plugin' => 'vendor/optional, integration disabled without plugin']));
    $run('exact optional symbol', '<?php new \Optional\Plugin();', true, [], ['--optional', $temp . '/optional.json']);
    $run('optional typo', '<?php new \Optional\Typo();', false, ['Optional\\Typo'], ['--optional', $temp . '/optional.json']);
    file_put_contents($temp . '/optional.json', json_encode(['Example\\Missing' => 'not allowed']));
    $run('no own namespace exemptions', '<?php new \Example\Missing();', false, ['Invalid optional dependency'], ['--optional', $temp . '/optional.json']);
    file_put_contents($temp . '/optional.json', json_encode(['Optional\\*' => 'not allowed']));
    $run('no wildcard exemptions', '<?php new \Optional\Typo();', false, ['Invalid optional dependency'], ['--optional', $temp . '/optional.json']);
    $run('missing inventory', '<?php new \Example\Item();', false, ['does not exist'], ['--optional', $temp . '/missing.json']);
    $run('missing dependencies', '<?php new \Vendor\Dependency();', false, ['no Composer autoload maps'], ['--vendor', $temp . '/absent']);
    mkdir($temp . '/vendor/craftcms/cms/src/behaviors', 0777, true);
    file_put_contents($temp . '/vendor/craftcms/cms/src/behaviors/CustomFieldBehavior.php.template', '<?php namespace craft\\behaviors; class CustomFieldBehavior {}');
    $run('generated Craft behavior', '<?php new \\craft\\behaviors\\CustomFieldBehavior();', true);
    $run('generated Craft behavior typo', '<?php new \\craft\\behaviors\\CustomFieldsBehavior();', false, ['CustomFieldsBehavior']);

    mkdir($temp . '/tests/php-check', 0777, true);
    mkdir($temp . '/tests/runtime', 0777, true);
    mkdir($temp . '/.github/scripts', 0777, true);
    copy($root . '/prepare.php', $temp . '/tests/php-check/prepare.php');
    copy(dirname($root, 2) . '/.github/scripts/prepare-check-dependencies.php', $temp . '/.github/scripts/prepare-check-dependencies.php');
    $prepare = static function() use ($temp): array {
        $process = proc_open([PHP_BINARY, $temp . '/tests/php-check/prepare.php'], [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return [proc_close($process), $output];
    };
    $manifest = [
        'name' => 'vendor/plugin',
        'require' => ['php' => '^8.2', 'vendor/production' => '^1.0'],
        'require-dev' => ['vendor/testing' => '^2.0'],
    ];
    $original = json_encode($manifest);
    file_put_contents($temp . '/composer.json', $original);
    file_put_contents($temp . '/composer.lock', 'retained development lock');
    file_put_contents($temp . '/tests/runtime/composer.lock', 'retained integration lock');
    [$status, $output] = $prepare();
    if (PHP_VERSION !== '8.2.0') {
        if ($status === 0 || !str_contains($output, 'requires PHP 8.2.0')) {
            throw new RuntimeException('A newer interpreter must not qualify the minimum runtime: ' . $output);
        }
        $assertions++;
    } else {
        if ($status !== 0) {
            throw new RuntimeException('Minimum dependency preparation failed: ' . $output);
        }
        $prepared = json_decode(file_get_contents($temp . '/.cache/verbb-tests/app/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        if ($prepared['config']['platform'] !== ['php' => '8.2.0', 'php-64bit' => '8.2.0'] || $prepared['require'] !== array_merge($manifest['require-dev'], [$manifest['name'] => '*'])) {
            throw new RuntimeException('Static and behavioral checks must use the same minimum dependency manifest.');
        }
        $assertions++;
        if ($prepared['repositories'][0]['url'] !== '../../..' || $prepared['repositories'][0]['options']['reference'] !== 'config') {
            throw new RuntimeException('The plugin path must match the integration runtime.');
        }
        $assertions++;
        if (file_get_contents($temp . '/composer.json') !== $original || file_get_contents($temp . '/composer.lock') !== 'retained development lock' || file_get_contents($temp . '/.cache/verbb-tests/app/composer.lock') !== 'retained integration lock') {
            throw new RuntimeException('Minimum preparation must reuse the integration lock and leave development dependencies untouched.');
        }
        $assertions++;
        unlink($temp . '/tests/runtime/composer.lock');
        [$status, $output] = $prepare();
        if ($status === 0 || !str_contains($output, 'retained integration dependency lock is required')) {
            throw new RuntimeException('A missing integration lock must fail instead of resolving fresh dependencies: ' . $output);
        }
        $assertions++;
    }
    echo "Passed {$assertions} PHP verification regression scenarios.\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temp);
}
