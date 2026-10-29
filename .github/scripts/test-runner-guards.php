<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$directory = sys_get_temp_dir() . '/verbb-runner-' . bin2hex(random_bytes(6));
mkdir($directory);
$file = $directory . '/RunnerProbeTest.php';
file_put_contents($file, <<<'PROBE'
<?php
final class RunnerProbeTest extends \PHPUnit\Framework\TestCase
{
    public function testPass(): void { self::assertTrue(true); }
    public function testFailure(): void { self::assertTrue(false); }
    public function testRisky(): void {}
    public function testSkipped(): void { self::markTestSkipped('Probe'); }
    public function testIncomplete(): void { self::markTestIncomplete('Probe'); }
    public function testError(): void { throw new \RuntimeException('Probe'); }
}
PROBE);
try {
    foreach ([['Pass', null, 0], ['Failure', null, 1], ['Risky', '--fail-on-risky', 1], ['Skipped', '--fail-on-skipped', 1], ['Incomplete', '--fail-on-incomplete', 1], ['Error', null, 2], ['Absent', '--fail-on-empty-test-suite', 1]] as [$test, $flag, $expected]) {
        $command = [PHP_BINARY, '-d', 'auto_prepend_file=' . __DIR__ . '/pest-exit.php', $root . '/.cache/verbb-tests/app/vendor/pestphp/pest/bin/pest', '--no-configuration', '--colors=never', '--test-directory=' . $directory, '--filter=test' . $test, $file];
        if ($flag !== null) $command[] = $flag;
        $process = proc_open($command, [0 => STDIN, 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $root);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $status = proc_close($process);
        if ($status !== $expected) {
            throw new RuntimeException("$test returned $status instead of $expected:\n$output");
        }
        echo "$test: expected exit $status on PHP " . PHP_VERSION . "\n";
    }
} finally {
    unlink($file);
    rmdir($directory);
}
