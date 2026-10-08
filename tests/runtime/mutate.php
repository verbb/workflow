<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__, 2);
chdir($root);
$directory = CRAFT_BASE_PATH . '/../mutations';
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$lock = fopen(CRAFT_BASE_PATH . '/../run.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    throw new RuntimeException('Another suite owns the test installation.');
}

// Small, intentional mutations verify behavioural safeguards rather than chasing a coverage percentage.
$mutations = [
    'required-notes' => [
        'file' => 'src/models/Review.php',
        'from' => 'if ($this->role === self::ROLE_PUBLISHER && $this->status === self::STATUS_APPROVED',
        'to' => 'if (false && $this->role === self::ROLE_PUBLISHER && $this->status === self::STATUS_APPROVED',
        'filter' => 'requires publisher notes for native draft application',
    ],
    'draft-lock' => [
        'file' => 'src/services/Service.php',
        'from' => '$settings->lockDraftSubmissions &&',
        'to' => 'false &&',
        'filter' => 'enforces pending draft locking for each workflow role',
    ],
    'completed-filter' => [
        'file' => 'src/elements/db/SubmissionQuery.php',
        'from' => 'if ($this->isComplete !== null)',
        'to' => 'if ($this->isComplete)',
        'filter' => 'filters completed and pending flags independently',
    ],
    'reviewer-stage' => [
        'file' => 'src/services/Submissions.php',
        'from' => 'if (!isset($completedGroups[$group->uid]))',
        'to' => 'if (true)',
        'filter' => 'requires a separate recorded decision for every stage',
        'occurrence' => 'last',
    ],
];

function runMutationCheck(array $command, string $log, array $environment): int
{
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes, null, $environment);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start mutation check.');
    }
    return proc_close($process);
}

$report = [];
foreach ($mutations as $name => $mutation) {
    $source = file_get_contents($root . '/' . $mutation['file']);
    $hash = hash('sha256', $source);
    $xml = $directory . '/' . $name . '.xml';
    $log = $directory . '/' . $name . '.log';
    $file = $directory . '/' . $name . '.php';
    $command = [PHP_BINARY, 'tests/runtime/pest.php', '--configuration', 'phpunit.craft.xml', '--fail-on-empty-test-suite', '--filter', $mutation['filter'], '--log-junit', $xml];
    $environment = getenv();
    unset($environment['WORKFLOW_TEST_MUTANT']);
    $baseline = runMutationCheck($command, $directory . '/' . $name . '-baseline.log', $environment);
    if ($baseline !== 0) {
        throw new RuntimeException("Baseline failed for $name; see $directory/$name-baseline.log");
    }
    $position = ($mutation['occurrence'] ?? '') === 'last' ? strrpos($source, $mutation['from']) : strpos($source, $mutation['from']);
    if ($position === false) {
        throw new RuntimeException("Mutation no longer matches source: $name");
    }
    file_put_contents($file, substr_replace($source, $mutation['to'], $position, strlen($mutation['from'])));
    try {
        if (runMutationCheck([PHP_BINARY, '-l', $file], $directory . '/' . $name . '-lint.log', $environment) !== 0) {
            throw new RuntimeException("Invalid mutation syntax: $name");
        }
        unlink($xml);
        $environment['WORKFLOW_TEST_MUTANT'] = $name;
        $exit = runMutationCheck($command, $log, $environment);
        $results = is_file($xml) ? simplexml_load_file($xml) : false;
        $suite = $results ? $results->testsuite[0] : null;
        $killed = $suite && (int)$suite['tests'] > 0 && (int)$suite['failures'] > 0 && (int)$suite['errors'] === 0 && $exit !== 0;
        $report[$name] = ['status' => $killed ? 'killed' : 'survived-or-invalid', 'exitCode' => $exit, 'log' => basename($log)];
        echo $name . ': ' . $report[$name]['status'] . PHP_EOL;
    } finally {
        unlink($file);
        if (hash_file('sha256', $root . '/' . $mutation['file']) !== $hash) {
            throw new RuntimeException('Production source changed during mutation testing.');
        }
    }
}
file_put_contents($directory . '/result.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
exit(count(array_filter($report, fn($result) => $result['status'] !== 'killed')) ? 1 : 0);
