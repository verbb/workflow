<?php

it('approves complex content without creating submissions for nested entries', function(string $action, int $site) {
    $fixture = workflowSubmission('draft', $site, context: ['complexContent' => true]);
    expect($fixture['content']['blocks'])->toHaveCount(1);
    $result = workflowApprove($fixture, $action, ['body' => ['fields' => ['details' => [['col1' => 'Revised table cell']], 'body' => ['entries' => ['new1' => ['type' => 'textBlock', 'title' => 'Revised block', 'fields' => ['summary' => 'Revised nested content']]], 'sortOrder' => ['new1']]]]]);
    workflowAssertSuccess($result);
    expect($result['content']['related'])->toBe($fixture['content']['related']);
    expect($result['content']['blocks'])->toBe([['title' => 'Revised block', 'summary' => 'Revised nested content']]);
    expect($result['content']['details'][0]['label'])->toBe('Revised table cell');
    expect($result['submission']['reviewCount'])->toBe(2);
    expect($result['submissionCount'])->toBe(1);
})->with(['approve-submission', 'approve-apply-submission'])->with([0, 1]);

it('approves with entry versioning disabled', function() {
    $fixture = workflowSubmission('draft', context: ['versioning' => false]);
    $result = workflowApprove($fixture);
    workflowAssertSuccess($result);
    expect($result['submission']['complete'])->toBeTrue();
    expect($result['revisionCount'])->toBe(0);
});

it('preserves scheduled and expired publishing dates', function(array $attributes, string $status) {
    $fixture = workflowSubmission('draft');
    workflowRequest(['target' => $fixture['target'], 'alter' => 'attributes', 'attributes' => $attributes]);
    $result = workflowApprove($fixture);
    workflowAssertSuccess($result);
    expect($result['canonical']['status'])->toBe($status);
    expect($result['submission']['complete'])->toBeTrue();
})->with([
    'scheduled' => [['postDate' => '+1 year'], 'pending'],
    'expired' => [['postDate' => '-2 years', 'expiryDate' => '-1 year'], 'expired'],
]);

it('keeps the live entry unchanged when review persistence fails', function(string $kind, string $path) {
    $fixture = workflowSubmission($kind);
    $result = $path === 'status' ? workflowStatus($fixture, overrides: ['context' => ['failReviewSave' => true]]) : workflowApprove($fixture, overrides: ['context' => ['failReviewSave' => true]]);
    expect($result['exception']['message'])->toBe('Injected review persistence failure');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['submission']['pending'])->toBeTrue();
    expect($result['canonical'])->toBe($fixture['canonical']);
})->with(['draft', 'legacy'])->with(['entry', 'status']);
