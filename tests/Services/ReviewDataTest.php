<?php

it('renders raw and previously encoded notes safely', function(string $notes) {
    $review = new verbb\workflow\models\Review();
    $review->setNotes($notes);
    expect($review->getNotes())->toBe('&lt;strong&gt;Review &amp; feedback&lt;/strong&gt; 😀');
})->with(['<strong>Review & feedback</strong> 😀', '&lt;strong&gt;Review &amp; feedback&lt;/strong&gt; 😀']);

it('validates the stored byte length of review notes', function(string $notes, bool $valid) {
    $review = new verbb\workflow\models\Review();
    $review->setNotes($notes);
    expect($review->validate())->toBe($valid);
})->with([
    'boundary' => [str_repeat('a', 65535), true],
    'overflow' => [str_repeat('a', 65536), false],
    'encoded overflow' => [str_repeat('&', 14000), false],
    'multibyte overflow' => [str_repeat('😀', 17000), false],
]);

it('refuses malformed review snapshot encoding', function() {
    $review = new verbb\workflow\models\Review(['data' => ['title' => "\xB1\x31"]]);
    expect($review->validate())->toBeFalse();
    expect($review->hasErrors('data'))->toBeTrue();
});

it('tracks additions removals and changed field values', function() {
    $content = verbb\workflow\Workflow::$plugin->getContent();
    $diff = $content->getDiff(['title' => 'Before', 'slug' => 'removed', 'fields' => ['summary:1' => 'Original']], ['title' => 'After', 'enabled' => true, 'fields' => ['summary:1' => 'Revised']]);
    expect($diff['title']['type'])->toBe('change');
    expect($diff['slug']['type'])->toBe('remove');
    expect($diff['enabled']['type'])->toBe('add');
    expect($diff['fields']['summary:1']['type'])->toBe('change');
});

it('excludes reviewer stage metadata from content differences and revision attributes', function() {
    $content = verbb\workflow\Workflow::$plugin->getContent();
    expect($content->getDiff(['title' => 'Same'], ['title' => 'Same', 'reviewerGroupUid' => 'stage-uid']))->toBe([]);
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 1]);
    $reviewed = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($reviewed);
    $result = workflowRequest(['route' => 'workflow/reviews/get-compare-modal-body', 'body' => ['reviewId' => $reviewed['submission']['reviewId']]]);
    workflowAssertSuccess($result);
    expect($result['html'])->not->toContain('reviewerGroupUid');
});
