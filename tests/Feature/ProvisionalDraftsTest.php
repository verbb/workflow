<?php

it('creates updates reopens and discards a provisional draft without submitting', function(int $site) {
    $f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $entry = workflowRequest(['fixture' => 'canonical', 'author' => 'canonicalEditor', 'siteId' => $f['sites'][$site]]);
    $draft = workflowSaveDraft($entry, ['provisional' => true, 'fields' => ['summary' => 'Autosaved content']], followResponse: true);
    workflowAssertSuccess($draft);
    expect($draft['entry']['provisional'])->toBeTrue();
    expect($draft['entry']['summary'])->toBe('Autosaved content');
    expect($draft['submissionCount'])->toBe(0);
    expect($draft['canonical'])->toBe($entry['canonical']);
    $saved = workflowSaveDraft($draft, ['provisional' => true, 'fields' => ['summary' => 'Second autosave']]);
    workflowAssertSuccess($saved);
    expect(workflowInspect($draft)['entry']['summary'])->toBe('Second autosave');
    expect($saved['draftCount'])->toBe(1);
    $deleted = workflowRequest(['target' => $draft['target'], 'siteId' => $draft['target']['siteId'], 'route' => 'elements/delete-draft', 'user' => 'canonicalEditor', 'body' => ['provisional' => true]]);
    workflowAssertSuccess($deleted);
    expect($deleted['entry'])->toBeNull();
    expect($deleted['canonical'])->toBe($entry['canonical']);
    expect($deleted['submissionCount'])->toBe(0);
})->with([0, 1]);

it('converts provisional edits into a submitted draft before approval', function(bool $convertFirst, int $stages) {
    $fixture = workflowRequest(['fixture' => 'provisional', 'author' => 'canonicalEditor', 'context' => ['reviewStages' => $stages]]);
    expect($fixture['entry']['provisional'])->toBeTrue();
    if ($convertFirst) {
        $fixture = workflowSaveDraft($fixture, ['dropProvisional' => true]);
        workflowAssertSuccess($fixture);
        expect($fixture['entry']['provisional'])->toBeFalse();
    }
    $submitted = workflowSaveDraft($fixture, ['dropProvisional' => true, 'workflow-action' => 'save-submission']);
    workflowAssertSuccess($submitted);
    expect($submitted['entry']['provisional'])->toBeFalse();
    expect($submitted['submission']['pending'])->toBeTrue();
    foreach (array_slice(['reviewerOne', 'reviewerTwo'], 0, $stages) as $reviewer) {
        $submitted = workflowAction($submitted, 'approve-review', $reviewer);
        workflowAssertSuccess($submitted);
    }
    $approved = workflowApprove($submitted);
    workflowAssertSuccess($approved);
    expect($approved['canonical']['summary'])->toBe('Revised summary');
    expect($approved['submission']['reviewCount'])->toBe($stages + 2);
})->with([false, true])->with([0, 1, 2]);

it('blocks autosave changes to a pending submitted draft', function() {
    $fixture = workflowSubmission('draft');
    $result = workflowSaveDraft($fixture, ['fields' => ['summary' => 'Forbidden autosave']], 'editor');
    workflowAssertDenied($result);
    expect($result['entry']['summary'])->toBe('Revised summary');
    expect($result['submission']['reviewCount'])->toBe(1);
});

it('does not publish unrelated provisional edits when approving a submitted draft', function() {
    $submitted = workflowSubmission('draft');
    $canonical = $submitted;
    $canonical['target']['elementId'] = $canonical['target']['canonicalId'];
    $canonical['target']['draftId'] = null;
    $private = workflowSaveDraft($canonical, ['provisional' => true, 'fields' => ['summary' => 'Unrelated private edit']], 'publisher', true);
    workflowAssertSuccess($private);
    expect($private['entry']['provisional'])->toBeTrue();
    $approved = workflowApprove($submitted);
    workflowAssertSuccess($approved);
    expect($approved['canonical']['summary'])->toBe('Revised summary');
    expect($approved['submission']['reviewCount'])->toBe(2);
});
