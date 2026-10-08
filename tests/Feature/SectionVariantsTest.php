<?php

it('submits and approves singles structures and different propagation policies', function(string $sectionType, string $propagation, int $site) {
    $fixture = workflowSubmission('draft', $site, author: 'canonicalEditor', context: ['sectionType' => $sectionType, 'propagation' => $propagation, 'enabledSections' => '*', 'reviewStages' => 1]);
    $reviewed = workflowAction($fixture, 'approve-review', 'reviewerOne');
    workflowAssertSuccess($reviewed);
    $approved = workflowApprove($reviewed);
    workflowAssertSuccess($approved);
    expect($approved['submission']['complete'])->toBeTrue();
    expect($approved['canonical']['summary'])->toBe('Revised summary');
    expect($approved['submission']['reviewCount'])->toBe(3);
    expect($approved['submissionCount'])->toBe(1);
    if ($sectionType !== 'single') {
        expect($approved['localizations'])->toHaveCount($propagation === 'all' ? 2 : 1);
    }
})->with(['single', 'structure'])->with(['all', 'none'])->with([0, 1]);

it('preserves disabled site state when approving and applying content', function() {
    $fixture = workflowSubmission('draft', 1);
    $disabled = workflowRequest(['target' => $fixture['target'], 'siteId' => $fixture['target']['siteId'], 'alter' => 'attributes', 'attributes' => ['enabledForSite' => false]]);
    workflowAssertSuccess($disabled);
    $fixture['entry']['enabledForSite'] = false;
    $approved = workflowApprove($fixture, 'approve-apply-submission');
    workflowAssertSuccess($approved);
    expect($approved['canonical']['enabledForSite'])->toBeFalse();
    expect($approved['canonical']['summary'])->toBe('Revised summary');
    expect($approved['submission']['complete'])->toBeTrue();
});

it('propagates shared fields while preserving other localized content', function() {
    $fixture = workflowSubmission('draft', 1);
    $f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $target = $fixture['target'];
    $target['siteId'] = $f['sites'][0];
    $target['elementId'] = $target['canonicalId'];
    $target['draftId'] = null;
    $beforeLocalized = craft\elements\Entry::find()->id($target['canonicalId'])->siteId($f['sites'][0])->status(null)->one()->getFieldValue('localizedSummary');
    $approved = workflowApprove($fixture);
    workflowAssertSuccess($approved);
    $after = workflowRequest(['target' => $target, 'siteId' => $target['siteId']]);
    expect($after['canonical']['summary'])->toBe('Revised summary');
    $primary = craft\elements\Entry::find()->id($target['canonicalId'])->siteId($f['sites'][0])->status(null)->one();
    $secondary = craft\elements\Entry::find()->id($target['canonicalId'])->siteId($f['sites'][1])->status(null)->one();
    expect($primary->getFieldValue('localizedSummary'))->toBe($beforeLocalized);
    expect($secondary->getFieldValue('localizedSummary'))->toBe('Revised localized content');
});

it('removes nested content and relations only after the draft is approved', function() {
    $fixture = workflowSubmission('draft', context: ['complexContent' => true]);
    $edited = workflowEdit($fixture, 'publisher', ['fields' => ['body' => ['entries' => [], 'sortOrder' => []], 'relatedEntries' => [], 'details' => []]]);
    workflowAssertSuccess($edited);
    expect($edited['content'])->toBe($fixture['content']);
    $approved = workflowApprove($edited);
    workflowAssertSuccess($approved);
    expect($approved['content']['related'])->toBe([]);
    expect($approved['content']['blocks'])->toBe([]);
    expect($approved['content']['details'])->toBeNull();
    expect($approved['submissionCount'])->toBe(1);
});
