<?php

it('enforces pending draft locking for each workflow role', function(string $user, bool $allowed) {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $result = workflowEdit($fixture, $user, ['fields' => ['summary' => 'Edited while pending']]);
    if ($allowed) {
        workflowAssertSuccess($result);
    } else {
        workflowAssertDenied($result);
    }
    expect($result['entry']['summary'])->toBe($allowed ? 'Edited while pending' : 'Revised summary');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical']['summary'])->toBe('Original summary');
})->with([['editor', false], ['editorTwo', false], ['reviewerOne', true], ['reviewerTwo', false], ['publisher', true], ['outsider', false], ['admin', true]]);

it('allows the editor to edit when draft locking is disabled', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowEdit($fixture, 'editor', ['fields' => ['summary' => 'Unlocked change']], ['lockDraftSubmissions' => false]);
    workflowAssertSuccess($result);
    expect($result['entry']['summary'])->toBe('Unlocked change');
    expect($result['submission']['reviewCount'])->toBe(1);
});

it('does not bypass locking with an unknown action', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowEdit($fixture, 'editor', ['workflow-action' => 'unknown-action', 'fields' => ['summary' => 'Blocked change']]);
    expect($result['entry']['summary'])->toBe('Revised summary');
    expect($result['submission']['reviewCount'])->toBe(1);
});

it('publishes the publishers edits with the approval snapshot', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowApprove($fixture, overrides: ['body' => ['fields' => ['summary' => 'Final publisher edit']]]);
    workflowAssertSuccess($result);
    expect($result['canonical']['summary'])->toBe('Final publisher edit');
    expect(array_values($result['history'][0]['data']['fields']))->toContain('Final publisher edit');
    expect(array_values($result['history'][1]['data']['fields']))->toContain('Revised summary');
});
