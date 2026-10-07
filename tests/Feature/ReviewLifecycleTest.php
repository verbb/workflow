<?php

it('completes an ordered review cycle before publication', function(string $kind, int $stages) {
    $fixture = workflowSubmission($kind, context: ['reviewStages' => $stages]);
    expect($fixture['nextReviewer'])->toBe('reviewersOne');
    foreach (array_slice(['reviewerOne', 'reviewerTwo'], 0, $stages) as $index => $user) {
        $fixture = workflowAction($fixture, 'approve-review', $user);
        workflowAssertSuccess($fixture);
        expect($fixture['submission']['pending'])->toBeTrue();
        expect($fixture['submission']['complete'])->toBeFalse();
        expect($fixture['history'][0]['role'])->toBe('reviewer');
        expect($fixture['submission']['reviewCount'])->toBe($index + 2);
        expect($fixture['nextReviewer'])->toBe($index + 1 < $stages ? 'reviewersTwo' : null);
    }
    $published = workflowApprove($fixture);
    workflowAssertSuccess($published);
    expect($published['submission']['complete'])->toBeTrue();
    expect($published['submission']['reviewCount'])->toBe($stages + 2);
    expect($published['canonical']['enabled'])->toBeTrue();
})->with(['frontend', 'draft', 'canonical'])->with([1, 2]);

it('only allows the current reviewer to record the next review', function(string $user) {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $result = workflowAction($fixture, 'approve-review', $user);
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['nextReviewer'])->toBe('reviewersOne');
    expect($result['canonical'])->toBe($fixture['canonical']);
})->with(['reviewerTwo', 'editor', 'outsider']);

it('cannot approve the same reviewer stage twice', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $first = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($first);
    $duplicate = workflowAction($first, 'approve-review', 'reviewerOne');
    expect($duplicate['submission']['reviewCount'])->toBe(2);
    expect($duplicate['nextReviewer'])->toBe('reviewersTwo');
});

it('restarts review after rejection and resubmission', function(string $action, string $user) {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $rejected = workflowAction($fixture, $action, $user);
    workflowAssertSuccess($rejected);
    expect($rejected['submission']['pending'])->toBeFalse();
    expect($rejected['submission']['complete'])->toBeFalse();
    expect($rejected['canonical']['summary'])->toBe('Original summary');
    $resubmitted = workflowResubmit($rejected, overrides: ['body' => ['fields' => ['summary' => 'Corrected submission']]]);
    workflowAssertSuccess($resubmitted);
    expect($resubmitted['submission']['id'])->toBe($fixture['submission']['id']);
    expect($resubmitted['submission']['reviewCount'])->toBe(3);
    expect($resubmitted['nextReviewer'])->toBe('reviewersOne');
    expect($resubmitted['history'][0]['data']['fields'])->not->toBe($resubmitted['history'][1]['data']['fields']);
    expect($resubmitted['history'][1]['status'])->toBe('rejected');
    $approved = workflowApprove($resubmitted);
    workflowAssertSuccess($approved);
    expect($approved['canonical']['summary'])->toBe('Corrected submission');
})->with([['reject-review', 'reviewerOne'], ['reject-submission', 'publisher']]);

it('lets an editor revoke and create a new submission', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $revoked = workflowAction($fixture, 'revoke-submission', $kind === 'canonical' ? 'canonicalEditor' : 'editor');
    workflowAssertSuccess($revoked);
    expect($revoked['submission']['complete'])->toBeTrue();
    expect($revoked['submission']['status'])->toBe('revoked');
    $resubmitted = workflowResubmit($revoked, $kind === 'canonical' ? 'canonicalEditor' : 'editor', ['body' => ['submissionId' => null, 'workflowReviewId' => null]]);
    workflowAssertSuccess($resubmitted);
    expect($resubmitted['submission']['id'])->not->toBe($fixture['submission']['id']);
    expect($resubmitted['submissionCount'])->toBe(2);
    expect($resubmitted['submission']['reviewCount'])->toBe(1);
})->with(['frontend', 'draft', 'canonical']);

it('does not let another editor resubmit rejected content', function() {
    $fixture = workflowSubmission('draft');
    $rejected = workflowAction($fixture, 'reject-submission');
    workflowAssertSuccess($rejected);
    $result = workflowResubmit($rejected, 'editorTwo');
    expect($result['submission']['status'])->toBe('rejected');
    expect($result['submission']['reviewCount'])->toBe(2);
});

it('can approve without applying content through a registered action', function() {
    $fixture = workflowSubmission('draft', context: ['approveOnly' => true]);
    $approved = workflowAction($fixture, 'approve-only-submission');
    workflowAssertSuccess($approved);
    expect($approved['submission']['complete'])->toBeTrue();
    expect($approved['submission']['status'])->toBe('approved');
    expect($approved['draftExists'])->toBeTrue();
    expect($approved['canonical']['summary'])->toBe('Original summary');
});

it('rejects duplicate pending submissions', function(string $kind) {
    $fixture = workflowSubmission($kind);
    $duplicate = workflowResubmit($fixture, $kind === 'canonical' ? 'canonicalEditor' : 'editor', ['body' => ['submissionId' => null, 'workflowReviewId' => null]]);
    expect($duplicate['submissionCount'])->toBe(1);
    expect($duplicate['submission']['reviewCount'])->toBe(1);
})->with(['frontend', 'draft', 'canonical']);
