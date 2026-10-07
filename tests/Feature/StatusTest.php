<?php

it('offers and applies submission status changes', function(string $kind, int $site) {
    $fixture = workflowSubmission($kind, $site);
    $fixture = workflowRequest(['target' => $fixture['target'], 'siteId' => $fixture['target']['siteId']]);
    expect(array_keys($fixture['submission']['statuses']))->toBe(['approved', 'rejected', 'revoked']);
    $result = workflowStatus($fixture);
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['error'])->toBeNull();
    expect($result['submission']['status'])->toBe('approved');
    expect($result['submission']['reviewCount'])->toBe(2);
    expect($result['draftExists'])->toBeFalse();
    if ($kind === 'legacy') {
        expect($result['canonical']['enabled'])->toBeTrue();
        expect($result['canonical']['enabledForSite'])->toBeTrue();
    }
})->with(['frontend', 'draft', 'legacy'])->with([0, 1]);

it('can reject or revoke a canonical submission without publishing it', function(string $status) {
    $fixture = workflowSubmission('legacy');
    $result = workflowStatus($fixture, $status);
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['submission']['status'])->toBe($status);
    expect($result['submission']['reviewCount'])->toBe(2);
    expect($result['canonical']['enabled'])->toBeFalse();
})->with(['rejected', 'revoked']);

it('rolls back canonical publication when required approval notes are missing', function() {
    $fixture = workflowSubmission('legacy');
    $result = workflowStatus($fixture, overrides: ['requireNotes' => true, 'body' => ['workflowNotes' => '']]);
    expect($result['submission']['status'])->toBe('pending');
    expect($result['submission']['reviewCount'])->toBe(1);
    expect($result['canonical']['enabled'])->toBeFalse();
});
