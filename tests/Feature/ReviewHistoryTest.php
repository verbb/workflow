<?php

it('preserves review note line breaks while keeping HTML escaped', function(string $newline, bool $encoded) {
    $fixture = workflowSubmission('draft');
    $notes = 'First & feedback' . $newline . $newline . '<strong>Second</strong><br>';
    $review = verbb\workflow\Workflow::$plugin->getReviews()->getReviewById($fixture['submission']['reviewId']);
    $review->setNotes($notes);
    expect(verbb\workflow\Workflow::$plugin->getReviews()->saveReview($review))->toBeTrue();

    // Cover both current encoded storage and legacy records containing plain text.
    if (!$encoded) {
        Craft::$app->getDb()->createCommand()->update('{{%workflow_reviews}}', ['notes' => $notes], ['id' => $review->id])->execute();
    }

    $result = workflowRequest([
        'route' => 'workflow/submissions/edit',
        'params' => ['submissionId' => $fixture['submission']['id']],
        'json' => false,
        'renderResponse' => true,
    ]);
    workflowAssertSuccess($result);
    preg_match('/<p>[^<]*Notes: .*?<\/p>/s', $result['html'], $matches);
    expect($matches)->not->toBeEmpty();
    expect($matches[0])->toContain('First &amp; feedback<br />' . $newline . '<br />' . $newline . '&lt;strong&gt;Second&lt;/strong&gt;&lt;br&gt;');
    expect($matches[0])->not->toContain('<strong>Second</strong>');
})->with(["\n", "\r\n", "\r"])->with([true, false]);

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
