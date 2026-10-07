<?php
it('installs the plugin into a clean Craft application', function() {
    expect(Craft::$app->getIsInstalled())->toBeTrue();
    expect(Craft::$app->getPlugins()->isPluginEnabled('workflow'))->toBeTrue();
});

it('registers assignable Workflow control-panel permissions on a fresh install', function() {
    $permissions = json_encode(Craft::$app->getUserPermissions()->getAllPermissions());
    expect($permissions)->toContain('accessPlugin-workflow');
    expect($permissions)->toContain('workflow-overview');
});
