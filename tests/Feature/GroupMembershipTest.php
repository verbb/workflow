<?php

it('allows either member of the current reviewer group but records one decision', function(string $reviewer) {
    workflowWithUsers(['outsider' => ['groups' => ['reviewersOne']]], function() use ($reviewer) {
        $fixture = workflowSubmission('draft', context: ['reviewStages' => 2, 'notifications' => true]);
        expect(workflowRecipients($fixture))->toBe(['outsider@example.test', 'reviewerOne@example.test']);
        $reviewed = workflowAction($fixture, 'approve-review', $reviewer);
        workflowAssertSuccess($reviewed);
        expect($reviewed['nextReviewer'])->toBe('reviewersTwo');
        $other = $reviewer === 'reviewerOne' ? 'outsider' : 'reviewerOne';
        $duplicate = workflowAction($reviewed, 'approve-review', $other);
        workflowAssertDenied($duplicate);
        expect($duplicate['submission']['reviewCount'])->toBe(2);
    });
})->with(['reviewerOne', 'outsider']);

it('requires a separate recorded decision for every stage when one user belongs to both', function() {
    workflowWithUsers(['reviewerOne' => ['groups' => ['reviewersOne', 'reviewersTwo']]], function() {
        $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
        $first = workflowAction($fixture, 'approve-review', 'reviewerOne');
        workflowAssertSuccess($first);
        expect($first['nextReviewer'])->toBe('reviewersTwo');
        $second = workflowAction($first, 'approve-review', 'reviewerOne');
        workflowAssertSuccess($second);
        expect($second['nextReviewer'])->toBeNull();
        expect($second['submission']['reviewCount'])->toBe(3);
        $third = workflowAction($second, 'approve-review', 'reviewerOne');
        workflowAssertDenied($third);
        expect($third['submission']['reviewCount'])->toBe(3);
    });
});

it('lets a reviewer publisher use their publisher authority without recording a reviewer decision', function() {
    workflowWithUsers(['reviewerOne' => ['groups' => ['reviewersOne', 'publishers']]], function() {
        $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
        $approved = workflowApprove($fixture, overrides: ['user' => 'reviewerOne']);
        workflowAssertSuccess($approved);
        expect($approved['history'][0]['role'])->toBe('publisher');
        expect($approved['submission']['complete'])->toBeTrue();
        expect($approved['submission']['reviewCount'])->toBe(2);
    });
});

it('uses current group membership when a reviewer loses their role', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    workflowWithUsers(['reviewerOne' => ['groups' => ['outsiders']], 'outsider' => ['groups' => ['reviewersOne']]], function() use ($fixture) {
        $denied = workflowAction($fixture, 'approve-review', 'reviewerOne');
        workflowAssertDenied($denied);
        expect($denied['submission']['reviewCount'])->toBe(1);
        $allowed = workflowAction($fixture, 'approve-review', 'outsider');
        workflowAssertSuccess($allowed);
        expect($allowed['nextReviewer'])->toBe('reviewersTwo');
    });
});

it('denies a publisher whose role was removed after opening the submission', function(string $path) {
    $fixture = workflowSubmission('draft');
    workflowWithUsers(['publisher' => ['groups' => ['outsiders']]], function() use ($fixture, $path) {
        $result = $path === 'entry' ? workflowApprove($fixture) : workflowStatus($fixture);
        workflowAssertDenied($result);
        expect($result['canonical'])->toBe($fixture['canonical']);
        expect($result['submission']['reviewCount'])->toBe(1);
    });
    workflowAssertSuccess(workflowApprove($fixture));
})->with(['entry', 'status']);

it('rechecks individual Craft permissions after the submission was opened', function(string $permission) {
    $fixture = workflowSubmission('draft');
    workflowWithPermissions(['publishers' => ['remove' => [$permission]]], function() use ($fixture) {
        $denied = workflowApprove($fixture);
        workflowAssertDenied($denied);
        expect($denied['submission']['reviewCount'])->toBe(1);
        expect($denied['canonical'])->toBe($fixture['canonical']);
    });
    workflowAssertSuccess(workflowApprove($fixture));
})->with(['savePeerEntries', 'savePeerEntryDrafts', 'editSite']);

it('does not advance an empty reviewer group automatically', function() {
    workflowWithUsers(['reviewerOne' => ['groups' => ['outsiders']]], function() {
        $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
        expect($fixture['nextReviewer'])->toBe('reviewersOne');
        $denied = workflowAction($fixture, 'approve-review', 'reviewerTwo');
        workflowAssertDenied($denied);
        expect($denied['submission']['reviewCount'])->toBe(1);
        // Publishers retain the existing override authority when a review stage has no members.
        workflowAssertSuccess(workflowApprove($fixture));
    });
});

it('applies a changed reviewer sequence to a pending submission', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $fixture['context'] = ['reviewGroups' => ['reviewersTwo', 'reviewersOne']];
    $denied = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertDenied($denied);
    $first = workflowAction($fixture, 'approve-review', 'reviewerTwo');
    workflowAssertSuccess($first);
    expect($first['nextReviewer'])->toBe('reviewersOne');
    $second = workflowAction($first, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($second);
    expect($second['nextReviewer'])->toBeNull();
});

it('does not reopen completed stages when the reviewer changes groups', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $first = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($first);
    workflowWithUsers(['reviewerOne' => ['groups' => ['outsiders']]], function() use ($first) {
        $view = workflowInspect($first);
        expect($view['nextReviewer'])->toBe('reviewersTwo');
        $second = workflowAction($first, 'approve-review', 'reviewerTwo');
        workflowAssertSuccess($second);
        expect($second['nextReviewer'])->toBeNull();
    });
});

it('replays pre-upgrade review history without stage metadata', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2]);
    $first = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($first);
    $legacy = workflowRequest(['target' => $first['target'], 'submissionId' => $first['submission']['id'], 'context' => $first['context'], 'alter' => 'stripReviewerMetadata']);
    workflowAssertSuccess($legacy);
    expect($legacy['nextReviewer'])->toBe('reviewersTwo');
    $second = workflowAction($legacy, 'approve-review', 'reviewerTwo');
    workflowAssertSuccess($second);
    expect($second['nextReviewer'])->toBeNull();
});

it('requires approval from a newly inserted group after earlier stages were completed', function() {
    $fixture = workflowSubmission('draft', context: ['reviewGroups' => ['reviewersTwo']]);
    $first = workflowAction($fixture, 'approve-review', 'reviewerTwo');
    workflowAssertSuccess($first);
    $first['context'] = ['reviewGroups' => ['reviewersOne', 'reviewersTwo']];
    expect(workflowInspect($first)['nextReviewer'])->toBe('reviewersOne');
    $second = workflowAction($first, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($second);
    expect($second['nextReviewer'])->toBeNull();
    expect($second['submission']['reviewCount'])->toBe(3);
});
