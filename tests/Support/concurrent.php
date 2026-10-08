<?php

function workflowStartRequest(array $input): array
{
    $process = proc_open([PHP_BINARY, __DIR__ . '/request.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start concurrent request.');
    }
    fwrite($pipes[0], json_encode($input, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    return [$process, $pipes];
}

function workflowFinishRequest(array $request): array
{
    [$process, $pipes] = $request;
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0) {
        throw new RuntimeException("Concurrent request failed: $errors\n$output");
    }
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}

function workflowWaitForFiles(array $files): void
{
    $deadline = microtime(true) + 15;
    do {
        clearstatcache();
        if (count(array_filter($files, 'is_file')) === count($files)) {
            return;
        }
        usleep(10000);
    } while (microtime(true) < $deadline);
    throw new RuntimeException('Timed out waiting for concurrent request barrier.');
}

function workflowRace(array $fixture, array $actions): array
{
    // Synchronize after both applications have booted; the race uses separate DB connections.
    $directory = CRAFT_BASE_PATH . '/../race-' . bin2hex(random_bytes(6));
    mkdir($directory);
    $requests = [];
    try {
        foreach ($actions as $index => $decision) {
            $action = is_array($decision) ? $decision['action'] : $decision;
            $actor = is_array($decision) ? $decision['user'] : 'publisher';
            $requests[] = workflowStartRequest([
                'target' => $fixture['target'], 'siteId' => $fixture['target']['siteId'],
                'context' => $fixture['context'] ?? [], 'user' => $actor,
                'barrier' => ['directory' => $directory, 'index' => $index],
                'route' => $fixture['target']['draftId'] ? (in_array($action, ['approve-submission', 'approve-apply-submission']) ? 'elements/apply-draft' : 'elements/save-draft') : 'elements/save',
                'body' => ['enabled' => $fixture['entry']['enabled'], 'enabledForSite' => $fixture['entry']['enabledForSite'], 'workflow-action' => $action, 'submissionId' => $fixture['submission']['id'], 'workflowReviewId' => $fixture['submission']['reviewId'], 'workflowNotes' => 'Concurrent decision'],
            ]);
        }
        workflowWaitForFiles(array_map(fn($index) => "$directory/ready-$index", array_keys($actions)));
        touch("$directory/go");
        $results = [];
        foreach ($requests as $index => $request) {
            $results[] = workflowFinishRequest($request);
            unset($requests[$index]);
        }
        return $results;
    } finally {
        foreach ($requests as [$process, $pipes]) {
            proc_terminate($process);
            foreach ([$pipes[1], $pipes[2]] as $pipe) {
                fclose($pipe);
            }
            proc_close($process);
        }
        foreach (glob("$directory/*") as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}
