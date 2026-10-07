<?php

it('approves submitted content through the entry editor', function(string $kind, string $action, int $site) {
    $fixture = workflowSubmission($kind, $site);
    // Read menu as the publisher in a separate request, just like opening the CP editor.
    $fixture = workflowRequest(['target' => $fixture['target'], 'siteId' => $fixture['target']['siteId']]);
    expect(array_column(array_column($fixture['menu'], 'params'), 'workflow-action'))->toContain($action);
    $result = workflowApprove($fixture, $action);
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['response']['errors'] ?? [])->toBe([]);
    expect($result['submission']['status'])->toBe('approved');
    expect($result['submission']['complete'])->toBeTrue();
    expect($result['submission']['pending'])->toBeFalse();
    expect($result['submission']['reviewCount'])->toBe(2);
    expect($result['draftExists'])->toBeFalse();
    if ($action === 'approve-submission') {
        expect($result['canonical']['enabled'])->toBeTrue();
        expect($result['canonical']['enabledForSite'])->toBeTrue();
    }
    if ($action === 'approve-apply-submission' && in_array($kind, ['legacy', 'canonical'])) {
        expect($result['canonical']['enabled'])->toBeFalse();
    }
    if ($kind === 'draft') {
        expect($result['canonical']['summary'])->toBe('Revised summary');
        expect($result['revisionCount'])->toBeGreaterThan(0);
    }
})->with(['frontend', 'cp', 'draft', 'legacy', 'canonical'])->with(['approve-submission', 'approve-apply-submission'])->with([0, 1]);

it('supports Craft native draft application', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $result = workflowApprove($fixture, overrides: ['route' => 'elements/apply-draft', 'body' => ['workflow-action' => null]]);
    expect($result['response']['errors'] ?? [])->toBe([]);
    expect($result['submission']['status'])->toBe('approved');
    expect($result['submission']['reviewCount'])->toBe(2);
})->with(['frontend', 'draft']);

it('does not let a non-publisher approve', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $result = workflowApprove($fixture, overrides: ['user' => 'outsider']);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['menu'])->toBe([]);
})->with(['frontend', 'draft', 'legacy']);

it('does not allow self approval without permission', function() {
    $fixture = workflowSubmission('frontend', author: 'selfPublisher');
    $result = workflowApprove($fixture, overrides: ['user' => 'selfPublisher']);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
});

it('rejects a stale review without applying content', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $result = workflowApprove($fixture, overrides: ['body' => ['workflowReviewId' => $fixture['submission']['reviewId'] + 10000]]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['canonical']['summary'])->toBe('Original summary');
})->with(['draft', 'legacy']);

it('validates publisher notes before applying content', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowApprove($fixture, overrides: ['requireNotes' => true, 'body' => ['workflowNotes' => '']]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['canonical']['summary'])->toBe('Original summary');
});

it('accepts an enabled front-end submission', function() {
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'user' => 'editor', 'body' => ['workflow-action' => 'save-submission', 'enabled' => true]]);
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['draftExists'])->toBeTrue();
});
