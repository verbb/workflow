<?php

it('limits GraphQL submissions and owners to the schema scopes', function(string $kind, bool $inactive, bool $section, bool $site, bool $visible) {
    $fixture = workflowSubmission($kind);
    $f = json_decode(file_get_contents(CRAFT_BASE_PATH . '/../fixtures.json'), true);
    $scope = ['workflowSubmissions:read'];
    if ($section) {
        $scope[] = 'sections.' . $f['sectionUid'] . ':read';
    }
    $scope[] = 'sites.' . Craft::$app->getSites()->getSiteById($f['sites'][$site ? 0 : 1])->uid . ':read';
    if ($inactive) {
        $scope[] = 'elements.inactive:read';
    }
    $id = $fixture['submission']['id'];
    $result = workflowRequest(['graphql' => '{ workflowSubmissions(id: ' . $id . ') { id ownerId owner { id } } }', 'scope' => $scope]);
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['graphql']['errors'] ?? [])->toBe([]);
    $rows = $result['graphql']['data']['workflowSubmissions'];
    if ($visible) {
        expect($rows)->toHaveCount(1);
        expect((int)$rows[0]['owner']['id'])->toBe($fixture['target']['canonicalId']);
    } else {
        expect($rows)->toBe([]);
    }
})->with([
    'live owner' => ['draft', false, true, true, true],
    'disabled owner denied' => ['canonical', false, true, true, false],
    'disabled owner allowed' => ['canonical', true, true, true, true],
    'section denied' => ['draft', true, false, true, false],
    'site denied' => ['draft', true, true, false, false],
]);

it('omits the GraphQL submission query without Workflow scope', function() {
    $result = workflowRequest(['graphql' => '{ workflowSubmissions { id } }', 'scope' => []]);
    expect($result['exception'] ?? null)->toBeNull();
    expect($result['graphql']['data'])->toBe([]);
    expect($result['graphql']['data']['workflowSubmissions'] ?? null)->toBeNull();
});
