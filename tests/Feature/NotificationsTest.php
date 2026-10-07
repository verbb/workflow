<?php

it('notifies the first reviewer when content is submitted', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2, 'notifications' => true]);
    expect(workflowRecipients($fixture))->toBe(['reviewerOne@example.test']);
    expect($fixture['mail'][0]['sent'])->toBeTrue();
});

it('notifies the next reviewer and then publishers', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2, 'notifications' => true]);
    $first = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($first);
    expect(workflowRecipients($first))->toBe(['reviewerTwo@example.test']);
    $second = workflowAction($first, 'approve-review', 'reviewerTwo');
    workflowAssertSuccess($second);
    expect(workflowRecipients($second))->toBe(['publisher@example.test', 'selfPublisher@example.test']);
});

it('notifies publishers directly when no review stage is configured', function() {
    $fixture = workflowSubmission('frontend', context: ['notifications' => true]);
    expect(workflowRecipients($fixture))->toBe(['publisher@example.test', 'selfPublisher@example.test']);
});

it('sends the editor one notification after rejection', function(string $action, string $user) {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 1, 'notifications' => true]);
    $result = workflowAction($fixture, $action, $user);
    workflowAssertSuccess($result);
    expect(workflowRecipients($result))->toBe(['editor@example.test']);
})->with([['reject-review', 'reviewerOne'], ['reject-submission', 'publisher']]);

it('honours notification settings and cancellation', function(array $context, int $count) {
    $fixture = workflowSubmission('draft', context: $context);
    $result = workflowApprove($fixture);
    workflowAssertSuccess($result);
    expect($result['mail'])->toHaveCount($count);
    foreach ($result['mail'] as $mail) {
        expect(array_keys($mail['to']))->toBe(['editor@example.test']);
    }
})->with([
    'all disabled' => [[], 0],
    'editor only' => [['notifications' => ['editorNotifications' => true, 'publishedAuthorNotifications' => false]], 1],
    'editor and author' => [['notifications' => true], 2],
    'cancelled by event' => [['notifications' => true, 'cancelMail' => true], 0],
]);

it('does not send approval mail for failures or repeated requests', function(string $failure) {
    $fixture = workflowSubmission('draft', context: ['notifications' => true]);
    if ($failure === 'repeat') {
        workflowAssertSuccess(workflowApprove($fixture));
        $result = workflowApprove($fixture);
    } else {
        $result = workflowApprove($fixture, overrides: $failure === 'notes' ? ['requireNotes' => true, 'body' => ['workflowNotes' => '']] : ['context' => ['failReviewSave' => true]]);
        expect($result['canonical']['summary'])->toBe('Original summary');
        expect($result['submission']['reviewCount'])->toBe(1);
    }
    expect($result['mail'])->toBe([]);
})->with(['notes', 'save failure', 'repeat']);

it('can notify editors of reviewer approval with reviewer reply details', function() {
    $fixture = workflowSubmission('draft', context: ['reviewStages' => 2, 'notifications' => true, 'reviewerApprovalNotifications' => true, 'editorNotificationsOptions' => ['replyToReviewer', 'ccReviewer']]);
    $result = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($result);
    expect(workflowRecipients($result))->toBe(['editor@example.test', 'reviewerTwo@example.test']);
    $editorMail = array_values(array_filter($result['mail'], fn($mail) => isset($mail['to']['editor@example.test'])))[0];
    expect(array_keys($editorMail['replyTo']))->toBe(['reviewerOne@example.test']);
    expect(array_keys($editorMail['cc']))->toBe(['reviewerOne@example.test']);
});
