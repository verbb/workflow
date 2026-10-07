<?php

use verbb\workflow\elements\Submission;

it('filters completed and pending flags independently including false values', function() {
    $pending = workflowSubmission('draft');
    $rejected = workflowAction(workflowSubmission('draft'), 'reject-submission');
    $revoked = workflowAction(workflowSubmission('draft'), 'revoke-submission', 'editor');
    $approved = workflowApprove(workflowSubmission('draft'));
    foreach ([$rejected, $revoked, $approved] as $result) {
        workflowAssertSuccess($result);
    }
    $ids = array_map(fn($fixture) => $fixture['submission']['id'], [$pending, $rejected, $revoked, $approved]);
    $query = fn() => Submission::find()->id($ids)->siteId($pending['target']['siteId'])->status(null)->orderBy(['id' => SORT_ASC]);
    expect($query()->isComplete(false)->ids())->toBe([$ids[0], $ids[1]]);
    expect($query()->isComplete(true)->ids())->toBe([$ids[2], $ids[3]]);
    expect($query()->isPending(false)->ids())->toBe([$ids[1], $ids[2], $ids[3]]);
    expect($query()->isPending(true)->ids())->toBe([$ids[0]]);
    expect($query()->isComplete(false)->isPending(false)->ids())->toBe([$ids[1]]);
    expect($query()->status('rejected')->ids())->toBe([$ids[1]]);
});

it('combines editor reviewer and publisher filters', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 1]);
    $reviewed = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($reviewed);
    $published = workflowApprove($reviewed);
    workflowAssertSuccess($published);
    $f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $query = Submission::find()->id($fixture['submission']['id'])->siteId($fixture['target']['siteId'])->editorId($f['users']['editor'])->reviewerId($f['users']['reviewerOne'])->publisherId($f['users']['publisher']);
    expect($query->ids())->toBe([$fixture['submission']['id']]);
});

it('keeps template query criteria consistent with element queries', function() {
    $fixture = workflowSubmission('draft');
    $variable = new verbb\workflow\variables\WorkflowVariable();
    $query = $variable->submissions(['ownerId' => $fixture['target']['canonicalId'], 'ownerSiteId' => $fixture['target']['siteId'], 'ownerDraftId' => $fixture['target']['draftId'], 'isPending' => true, 'isComplete' => false]);
    expect($query->ids())->toBe([$fixture['submission']['id']]);
});
