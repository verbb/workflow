<?php

it('records one final decision when different publishers act at once', function(array $actions, string $kind) {
    $fixture = workflowSubmission($kind, context: ['notifications' => ['publishedAuthorNotifications' => false]]);
    $results = workflowRace($fixture, [['action' => $actions[0], 'user' => 'publisher'], ['action' => $actions[1], 'user' => 'selfPublisher']]);
    $final = workflowInspect($fixture);
    expect($final['submission']['reviewCount'])->toBe(2);
    expect($final['submission']['status'])->toBeIn(['approved', 'rejected']);
    expect(array_merge(...array_column($results, 'mail')))->toHaveCount(1);
    expect($final['canonical']['summary'])->toBe($final['submission']['status'] === 'approved' ? $fixture['entry']['summary'] : $fixture['canonical']['summary']);
})->with([
    [['approve-submission', 'approve-submission']],
    [['approve-submission', 'reject-submission']],
    [['reject-submission', 'reject-submission']],
])->with(['draft', 'canonical']);

it('serializes an editor revocation against publisher approval', function() {
    $fixture = workflowSubmission('draft');
    workflowRace($fixture, [['action' => 'revoke-submission', 'user' => 'editor'], ['action' => 'approve-submission', 'user' => 'publisher']]);
    $final = workflowInspect($fixture);
    expect($final['submission']['reviewCount'])->toBe(2);
    expect($final['submission']['complete'])->toBeTrue();
    expect($final['submission']['status'])->toBeIn(['approved', 'revoked']);
    expect($final['canonical']['summary'])->toBe($final['submission']['status'] === 'approved' ? 'Revised summary' : 'Original summary');
});

it('records one stage decision when two members of a reviewer group act at once', function() {
    workflowWithUsers(['outsider' => ['groups' => ['reviewersOne']]], function() {
        $fixture = workflowSubmission('draft', context: ['reviewStages' => 2, 'notifications' => true]);
        $results = workflowRace($fixture, [['action' => 'approve-review', 'user' => 'reviewerOne'], ['action' => 'approve-review', 'user' => 'outsider']]);
        $final = workflowInspect($fixture);
        expect($final['submission']['reviewCount'])->toBe(2);
        expect($final['nextReviewer'])->toBe('reviewersTwo');
        expect(array_merge(...array_column($results, 'mail')))->toHaveCount(1);
    });
});
