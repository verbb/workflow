<?php

it('renders the review comparison through its controller', function() {
    $fixture = workflowSubmission('draft');
    $approved = workflowApprove($fixture);
    workflowAssertSuccess($approved);
    $result = workflowRequest(['route' => 'workflow/reviews/get-compare-modal-body', 'body' => ['reviewId' => $approved['submission']['reviewId']]]);
    workflowAssertSuccess($result);
    expect($result['html'])->toContain('workflow-compare-review-content');
});

it('does not compare reviews belonging to unrelated submissions', function() {
    $first = workflowSubmission('draft');
    $second = workflowSubmission('draft');
    $result = workflowRequest(['route' => 'workflow/reviews/compare', 'params' => ['newReviewId' => $first['submission']['reviewId'], 'oldReviewId' => $second['submission']['reviewId']]]);
    expect($result['exception']['class'])->toBe(yii\web\NotFoundHttpException::class);
});

it('requires management permission to delete a review', function(string $user, bool $allowed) {
    $fixture = workflowSubmission('draft');
    $approved = workflowApprove($fixture);
    workflowAssertSuccess($approved);
    $result = workflowRequest(['route' => 'workflow/reviews/delete-review', 'target' => $fixture['target'], 'user' => $user, 'body' => ['reviewId' => $fixture['submission']['reviewId']]]);
    if ($allowed) {
        workflowAssertSuccess($result);
    }
    expect($result['submission']['reviewCount'])->toBe($allowed ? 1 : 2);
    expect($result['canonical'])->toBe($approved['canonical']);
})->with([['publisher', true], ['editor', false], ['outsider', false]]);

it('deletes submission history without deleting its entry', function() {
    $fixture = workflowSubmission('draft');
    $approved = workflowApprove($fixture);
    $result = workflowRequest(['route' => 'workflow/submissions/delete-submission', 'target' => $fixture['target'], 'body' => ['submissionId' => $fixture['submission']['id']]]);
    workflowAssertSuccess($result);
    expect($result['submissionCount'])->toBe(0);
    expect($result['canonical'])->toBe($approved['canonical']);
});
