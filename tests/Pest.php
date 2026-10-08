<?php

require_once __DIR__ . '/Support/concurrent.php';

function workflowRequest(array $input): array
{
    $process = proc_open([PHP_BINARY, __DIR__ . '/Support/request.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fwrite($pipes[0], json_encode($input, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0) {
        throw new RuntimeException("Request exited $exit: $errors\n$output");
    }
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}

function workflowSubmission(string $kind, int $site = 0, string $author = 'editor', array $context = []): array
{
    $fixtures = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $siteId = $fixtures['sites'][$site];
    if ($kind === 'canonical' && $author === 'editor') {
        $author = 'canonicalEditor';
    }
    if ($kind === 'frontend') {
        $result = workflowRequest(['context' => $context, 'route' => 'workflow/elements/save-entry', 'cp' => false, 'siteId' => $siteId, 'user' => $author, 'body' => ['workflow-action' => 'save-submission']]);
    } elseif ($kind === 'cp') {
        $result = workflowRequest(['context' => $context, 'route' => 'entries/create', 'siteId' => $siteId, 'user' => $author]);
        expect($result['exception'] ?? null)->toBeNull();
        $result = workflowRequest(['context' => $context, 'route' => 'elements/save-draft', 'target' => $result['target'], 'siteId' => $siteId, 'user' => $author, 'body' => ['workflow-action' => 'save-submission', 'title' => 'CP article', 'fields' => ['summary' => 'Submitted content']]]);
    } else {
        $result = workflowRequest(['context' => $context, 'fixture' => $kind, 'siteId' => $siteId, 'user' => $author, 'author' => $author]);
        if ($kind !== 'legacy') {
            $result = workflowRequest(['context' => $context, 'route' => $kind === 'draft' ? 'elements/save-draft' : 'elements/save', 'target' => $result['target'], 'siteId' => $siteId, 'user' => $author, 'body' => ['workflow-action' => 'save-submission', 'enabled' => $result['entry']['enabled'], 'enabledForSite' => $result['entry']['enabledForSite']]]);
        }
    }
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['submission']['status'] ?? null)->toBe('pending');
    return $result;
}

function workflowApprove(array $fixture, string $action = 'approve-submission', array $overrides = []): array
{
    // Follow the actual menu route, so an incorrect endpoint is caught by the test.
    $menu = array_values(array_filter($fixture['menu'], fn($item) => ($item['params']['workflow-action'] ?? null) === $action));
    $route = $menu[0]['action'] ?? ($fixture['target']['draftId'] ? 'elements/apply-draft' : 'elements/save');
    return workflowRequest(array_replace_recursive([
        'context' => $fixture['context'] ?? [],
        'route' => $route, 'target' => $fixture['target'], 'siteId' => $fixture['target']['siteId'], 'user' => 'publisher',
        'body' => ['enabled' => $fixture['entry']['enabled'], 'enabledForSite' => $fixture['entry']['enabledForSite'], 'workflow-action' => $action, 'submissionId' => $fixture['submission']['id'], 'workflowReviewId' => $fixture['submission']['reviewId'], 'workflowNotes' => 'Approved in integration test'],
    ], $overrides));
}

function workflowStatus(array $fixture, string $status = 'approved', array $overrides = []): array
{
    return workflowRequest(array_replace_recursive([
        'context' => $fixture['context'] ?? [],
        'route' => 'workflow/submissions/save-submission', 'target' => $fixture['target'], 'siteId' => $fixture['target']['siteId'], 'user' => 'publisher',
        'body' => ['status' => $status, 'submissionId' => $fixture['submission']['id'], 'workflowReviewId' => $fixture['submission']['reviewId'], 'workflowNotes' => 'Status updated in integration test'],
    ], $overrides));
}

function workflowInspect(array $fixture, string $user = 'publisher'): array
{
    return workflowRequest(['target' => $fixture['target'], 'submissionId' => $fixture['submission']['id'] ?? null, 'siteId' => $fixture['target']['siteId'], 'context' => $fixture['context'] ?? [], 'user' => $user]);
}

function workflowAction(array $fixture, string $action, string $user = 'publisher', array $overrides = []): array
{
    $view = workflowInspect($fixture, $user);
    $items = array_merge($view['menu'] ?? [], ...array_values($view['menus'] ?? []));
    $items = array_values(array_filter($items, fn($item) => ($item['params']['workflow-action'] ?? null) === $action));
    $route = $items[0]['action'] ?? ($fixture['target']['draftId'] ? 'elements/save-draft' : 'elements/save');
    return workflowApprove($fixture, $action, array_replace_recursive(['route' => $route, 'user' => $user], $overrides));
}

function workflowResubmit(array $fixture, string $user = 'editor', array $overrides = []): array
{
    return workflowAction($fixture, 'save-submission', $user, $overrides);
}

function workflowEdit(array $fixture, string $user, array $body = [], array $context = []): array
{
    return workflowRequest(['route' => 'elements/save-draft', 'target' => $fixture['target'], 'siteId' => $fixture['target']['siteId'], 'user' => $user, 'context' => array_replace($fixture['context'] ?? [], $context), 'body' => $body]);
}

function workflowRecipients(array $result): array
{
    $recipients = array_merge([], ...array_map(fn($mail) => array_keys($mail['to']), $result['mail']));
    sort($recipients);
    return $recipients;
}

function workflowAssertSuccess(array $result): void
{
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['response']['errors'] ?? [])->toBe([]);
    expect($result['error'])->toBeNull();
}

function workflowBulk(array $fixtures, string $status, string $user = 'publisher'): array
{
    return workflowRequest([
        'route' => 'element-indexes/perform-action', 'user' => $user,
        'body' => ['elementType' => verbb\workflow\elements\Submission::class, 'elementAction' => verbb\workflow\elements\actions\SetStatus::class, 'elementIds' => array_column(array_column($fixtures, 'submission'), 'id'), 'status' => $status, 'viewState' => ['mode' => 'table', 'static' => false], 'source' => '*', 'context' => 'index', 'criteria' => ['siteId' => $fixtures[0]['target']['siteId'], 'status' => null]],
    ]);
}

function workflowWithUsers(array $changes, callable $test): void
{
    $changed = workflowRequest(['mutateUsers' => $changes]);
    workflowAssertSuccess($changed);
    try {
        $test();
    } finally {
        workflowAssertSuccess(workflowRequest(['mutateUsers' => $changed['restoreUsers']]));
    }
}

function workflowWithPermissions(array $changes, callable $test): void
{
    $changed = workflowRequest(['mutatePermissions' => $changes]);
    workflowAssertSuccess($changed);
    try {
        $test();
    } finally {
        workflowAssertSuccess(workflowRequest(['mutatePermissions' => $changed['restorePermissions']]));
    }
}

function workflowAssertDenied(array $result): void
{
    // A PHP/runtime failure must never count as a successful permission test.
    if (isset($result['exception'])) {
        expect($result['exception']['class'])->toBeIn([yii\web\ForbiddenHttpException::class, yii\web\UnauthorizedHttpException::class, craft\errors\InvalidElementException::class]);
        expect($result['exception']['message'])->toMatch('/authoriz|permission|Workflow|submission|approve|save/i');
    } else {
        expect($result['httpStatus'] ?? 200)->toBeGreaterThanOrEqual(400);
        expect($result['response']['errors'] ?? $result['response']['message'] ?? $result['error'])->not->toBeEmpty();
    }
}

function workflowSaveDraft(array $fixture, array $body, string $user = 'canonicalEditor', bool $followResponse = false): array
{
    return workflowRequest(['target' => $fixture['target'], 'submissionId' => $fixture['submission']['id'] ?? null, 'siteId' => $fixture['target']['siteId'], 'context' => $fixture['context'] ?? [], 'route' => 'elements/save-draft', 'user' => $user, 'body' => $body + ['provisional' => $fixture['entry']['provisional'] ?? false], 'followResponse' => $followResponse]);
}
