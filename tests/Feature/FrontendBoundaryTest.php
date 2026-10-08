<?php

it('submits front-end content from a custom fields location', function(bool $json) {
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'json' => $json, 'user' => 'editor', 'body' => ['workflow-action' => 'save-submission', 'fieldsLocation' => 'article', 'article' => ['summary' => 'Custom form fields'], 'fields' => []]]);
    workflowAssertSuccess($result);
    expect($result['entry']['summary'])->toBe('Custom form fields');
    expect($result['submission']['pending'])->toBeTrue();
    expect($result['submission']['reviewCount'])->toBe(1);
})->with([true, false]);

it('rejects anonymous front-end submissions before creating review history', function(bool $json) {
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'json' => $json, 'user' => 'guest', 'body' => ['workflow-action' => 'save-submission']]);
    expect($result['exception']['class'] ?? null)->toBeIn([yii\web\UnauthorizedHttpException::class, yii\web\ForbiddenHttpException::class]);
    expect($result['submission'] ?? null)->toBeNull();
})->with([true, false]);

it('rejects a front-end submission when Craft creation permission was removed', function() {
    workflowWithPermissions(['editors' => ['remove' => ['createEntries']]], function() {
        $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'user' => 'editor', 'body' => ['workflow-action' => 'save-submission']]);
        workflowAssertDenied($result);
        expect($result['submission'] ?? null)->toBeNull();
    });
    workflowAssertSuccess(workflowSubmission('frontend'));
});

it('does not record a front-end submission with invalid required content', function(bool $json) {
    $result = workflowRequest(['route' => 'workflow/elements/save-entry', 'cp' => false, 'json' => $json, 'user' => 'editor', 'body' => ['workflow-action' => 'save-submission', 'fields' => ['summary' => '']]]);
    expect($result['exception']['class'] ?? null)->toBe(yii\web\BadRequestHttpException::class);
    expect($result['exception']['message'])->toContain('Summary');
    expect($result['submission'] ?? null)->toBeNull();
})->with([true, false]);
