<?php

it('serializes competing decisions without duplicate reviews or notifications', function(array $actions, string $kind) {
    $fixture = workflowSubmission($kind, context: ['notifications' => ['publishedAuthorNotifications' => false]]);
    $results = workflowRace($fixture, $actions);
    $final = workflowInspect($fixture);
    expect($final['submission']['reviewCount'])->toBe(2);
    expect($final['submission']['status'])->toBeIn(['approved', 'rejected']);
    $mail = array_merge(...array_column($results, 'mail'));
    expect($mail)->toHaveCount(1);
    if ($final['submission']['status'] === 'approved') {
        expect($final['canonical']['summary'])->toBe($fixture['entry']['summary']);
    } else {
        expect($final['canonical']['summary'])->toBe('Original summary');
        expect($final['draftExists'])->toBe($kind === 'draft');
    }
})->with([
    'two approvals' => [['approve-submission', 'approve-submission']],
    'approve versus reject' => [['approve-submission', 'reject-submission']],
    'two rejections' => [['reject-submission', 'reject-submission']],
])->with(['draft', 'legacy']);
