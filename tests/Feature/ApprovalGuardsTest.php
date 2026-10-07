<?php

it('does not approve invalid content', function(string $kind, string $path) {
    $fixture = workflowSubmission($kind);
    workflowRequest(['target' => $fixture['target'], 'alter' => 'invalid']);
    $result = $path === 'status' ? workflowStatus($fixture) : workflowApprove($fixture);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    if ($kind === 'draft') {
        expect($result['canonical']['summary'])->toBe('Original summary');
        expect($result['draftExists'])->toBeTrue();
    } else {
        expect($result['canonical']['enabled'])->toBeFalse();
    }
})->with(['draft', 'legacy'])->with(['editor', 'status']);

it('requires Craft entry permissions in addition to the publisher role', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $result = workflowApprove($fixture, overrides: ['user' => 'limitedPublisher']);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['menu'])->toBe([]);
    expect($result['submission']['statuses'])->toBe([]);
})->with(['draft', 'legacy']);

it('rejects a submission ID belonging to another entry', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $other = workflowSubmission($kind);
    $result = workflowApprove($fixture, overrides: ['body' => ['submissionId' => $other['submission']['id'], 'workflowReviewId' => $other['submission']['reviewId']]]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical'])->toBe($fixture['canonical']);
})->with(['draft', 'legacy']);

it('rejects an approval posted to another site', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $result = workflowApprove($fixture, overrides: ['siteId' => $f['sites'][1]]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical'])->toBe($fixture['canonical']);
})->with(['draft', 'legacy']);

it('does not approve a deleted draft as its canonical entry', function(bool $migrate) {
    $fixture = workflowSubmission('draft');
    if ($migrate) {
        $migrated = workflowRequest(['target' => $fixture['target'], 'alter' => 'migrateReview', 'submissionId' => $fixture['submission']['id']]);
        expect($migrated['exception'] ?? null)->toBeNull();
        expect($migrated['submission']['snapshotDraftId'])->toBe($fixture['target']['draftId']);
    }
    $deleted = workflowRequest(['target' => $fixture['target'], 'alter' => 'deleteDraft']);
    expect($deleted['exception'] ?? null)->toBeNull();
    expect($deleted['submission']['reviewDraftId'])->toBeNull();
    expect($deleted['submission']['statuses'])->toBe([]);
    $result = workflowStatus($fixture);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical']['summary'])->toBe('Original summary');
})->with([false, true]);

it('does not apply a completed approval twice', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $first = workflowApprove($fixture);
    expect($first['submission']['status'])->toBe('approved');
    $second = workflowApprove($fixture);
    expect($second['submission']['reviewCount'])->toBe(2);
    expect($second['canonical'])->toBe($first['canonical']);
})->with(['draft', 'legacy']);

it('does not count draft identity metadata as an editorial change', function() {
    $content = verbb\workflow\Workflow::$plugin->getContent();
    expect($content->getDiff(['title' => 'Same'], ['title' => 'Same', 'draftId' => 42]))->toBe([]);
});

it('rejects a different draft of the same entry', function() {
    $fixture = workflowSubmission('draft');
    $sibling = workflowRequest(['target' => $fixture['target'], 'alter' => 'siblingDraft']);
    expect($sibling['exception'] ?? null)->toBeNull();
    $result = workflowApprove($fixture, overrides: ['body' => ['draftId' => $sibling['siblingDraftId']]]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['canonical']['summary'])->toBe('Original summary');
});

it('requires publisher notes for native draft application', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowApprove($fixture, overrides: ['requireNotes' => true, 'body' => ['workflow-action' => null, 'workflowNotes' => '']]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical']['summary'])->toBe('Original summary');
});

it('rejects stale status changes', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $result = workflowStatus($fixture, overrides: ['body' => ['workflowReviewId' => $fixture['submission']['reviewId'] + 10000]]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['canonical'])->toBe($fixture['canonical']);
})->with(['draft', 'legacy']);
