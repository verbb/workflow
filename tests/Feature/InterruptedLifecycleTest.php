<?php

it('restarts all review stages after repeated second-stage rejections', function(string $kind) {
    $fixture = workflowSubmission($kind, context: ['reviewStages' => 2]);
    for ($cycle = 0; $cycle < 3; $cycle++) {
        $first = workflowAction($fixture, 'approve-review', 'reviewerOne');
        workflowAssertSuccess($first);
        $rejected = workflowAction($first, 'reject-review', 'reviewerTwo');
        workflowAssertSuccess($rejected);
        expect($rejected['submission']['pending'])->toBeFalse();
        $fixture = workflowResubmit($rejected, $kind === 'canonical' ? 'canonicalEditor' : 'editor', ['body' => ['fields' => ['summary' => "Correction $cycle"]]]);
        workflowAssertSuccess($fixture);
        expect($fixture['nextReviewer'])->toBe('reviewersOne');
        expect($fixture['submission']['reviewCount'])->toBe(4 + $cycle * 3);
    }
    $fixture = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($fixture);
    $fixture = workflowAction($fixture, 'approve-review', 'reviewerTwo');
    workflowAssertSuccess($fixture);
    $approved = workflowApprove($fixture);
    workflowAssertSuccess($approved);
    expect($approved['canonical']['summary'])->toBe('Correction 2');
    expect($approved['submission']['reviewCount'])->toBe(13);
})->with(['frontend', 'draft', 'canonical']);

it('keeps submissions for sibling drafts independent', function() {
    $first = workflowSubmission('draft');
    $created = workflowRequest(['target' => $first['target'], 'alter' => 'siblingDraft']);
    workflowAssertSuccess($created);
    $second = workflowRequest(['target' => $created['siblingTarget'], 'route' => 'elements/save-draft', 'user' => 'editor', 'body' => ['workflow-action' => 'save-submission', 'fields' => ['summary' => 'Second draft content']]]);
    workflowAssertSuccess($second);
    expect($second['submission']['id'])->not->toBe($first['submission']['id']);
    $approved = workflowApprove($first);
    workflowAssertSuccess($approved);
    $secondView = workflowInspect($second);
    expect($secondView['submission']['pending'])->toBeTrue();
    expect($secondView['entry']['summary'])->toBe('Second draft content');
    $approvedSecond = workflowApprove($secondView);
    workflowAssertSuccess($approvedSecond);
    expect($approvedSecond['canonical']['summary'])->toBe('Second draft content');
});

it('keeps editor ownership after the entry author changes', function() {
    $fixture = workflowSubmission('draft');
    $changed = workflowRequest(['target' => $fixture['target'], 'alter' => 'attributes', 'attributes' => ['authorIds' => [json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true)['users']['editorTwo']]]]);
    workflowAssertSuccess($changed);
    $denied = workflowAction($fixture, 'revoke-submission', 'editorTwo');
    workflowAssertDenied($denied);
    expect($denied['submission']['pending'])->toBeTrue();
    $revoked = workflowAction($fixture, 'revoke-submission', 'editor');
    workflowAssertSuccess($revoked);
    expect($revoked['submission']['status'])->toBe('revoked');
});

it('preserves canonical changes to unedited fields when applying a pending draft', function() {
    $fixture = workflowSubmission('draft');
    $target = $fixture['target'];
    $target['elementId'] = $target['canonicalId'];
    $target['draftId'] = null;
    $changed = workflowRequest(['target' => $target, 'alter' => 'attributes', 'attributes' => ['slug' => 'updated-canonical-' . $target['canonicalId']]]);
    workflowAssertSuccess($changed);
    $approved = workflowApprove($fixture);
    workflowAssertSuccess($approved);
    $entry = craft\elements\Entry::find()->id($target['canonicalId'])->status(null)->one();
    expect($entry->slug)->toBe('updated-canonical-' . $target['canonicalId']);
    expect($entry->getFieldValue('summary'))->toBe('Revised summary');
});
