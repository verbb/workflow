<?php

it('submits ordinary HTML front-end forms', function(int $site) {
    $f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'json' => false, 'user' => 'editor', 'siteId' => $f['sites'][$site], 'body' => ['workflow-action' => 'save-submission']]);
    workflowAssertSuccess($result);
    expect($result['submission']['pending'])->toBeTrue();
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['draftExists'])->toBeTrue();
    expect($result['redirect'])->toBeString();
})->with([0, 1]);

it('allows explicitly configured self approval', function(array $context) {
    $fixture = workflowSubmission('draft', author: 'selfPublisher', context: $context);
    $result = workflowApprove($fixture, overrides: ['user' => 'selfPublisher']);
    workflowAssertSuccess($result);
    expect($result['submission']['complete'])->toBeTrue();
})->with([[['selfApprovalGroup' => true]], [['selfApprovalEvent' => true]]]);

it('honours different publisher groups on different sites', function() {
    $fixture = workflowSubmission('draft', 1, context: ['separateSitePublisher' => true]);
    $denied = workflowApprove($fixture);
    expect($denied['submission']['reviewCount'])->toBe(1);
    $allowed = workflowApprove($fixture, overrides: ['user' => 'secondaryPublisher']);
    workflowAssertSuccess($allowed);
    expect($allowed['submission']['complete'])->toBeTrue();
});

it('does not allow a publisher to edit a site without Craft site permission', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowApprove($fixture, overrides: ['user' => 'secondaryPublisher']);
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical'])->toBe($fixture['canonical']);
});

it('does not accept anonymous approvals', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowApprove($fixture, overrides: ['user' => 'guest']);
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical'])->toBe($fixture['canonical']);
});

it('requires editor notes when creating submissions', function() {
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'user' => 'editor', 'context' => ['editorNotesRequired' => true], 'body' => ['workflow-action' => 'save-submission', 'workflowNotes' => '']]);
    expect($result['exception'] ?? $result['error'])->not->toBeNull();
    expect($result['submission'] ?? null)->toBeNull();
});

it('refuses new submissions in disabled sections', function() {
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'user' => 'editor', 'context' => ['enabledSections' => []], 'body' => ['workflow-action' => 'save-submission']]);
    expect($result['submission'] ?? null)->toBeNull();
});

it('updates mixed selections through the Craft bulk action', function() {
    $own = workflowSubmission('draft', author: 'selfPublisher');
    $other = workflowSubmission('draft');
    $result = workflowBulk([$own, $other], 'approved', 'selfPublisher');
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['httpStatus'])->toBe(200);
    expect($result['response']['message'])->toBe('Status updated, with some failures due to validation errors.');
    expect(workflowInspect($own)['submission']['pending'])->toBeTrue();
    expect(workflowInspect($other)['submission']['complete'])->toBeTrue();
});

it('reports failure when every bulk item is unauthorized', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowBulk([$fixture], 'approved', 'outsider');
    expect($result['response']['success'] ?? false)->toBeFalse();
    expect(workflowInspect($fixture)['submission']['reviewCount'])->toBe(1);
});

it('rejects unsupported bulk statuses', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowBulk([$fixture], 'not-a-status');
    expect($result['exception']['class'] ?? null)->toBe(yii\web\BadRequestHttpException::class);
    expect(workflowInspect($fixture)['submission']['reviewCount'])->toBe(1);
});
