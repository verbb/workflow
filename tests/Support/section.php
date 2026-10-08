<?php

// Each variation owns a section; other fixtures retain their original layout and propagation policy.
$handle = 'variant' . bin2hex(random_bytes(5));
$section = new craft\models\Section([
    'name' => $handle, 'handle' => $handle,
    'type' => $context['sectionType'] ?? craft\models\Section::TYPE_CHANNEL,
    'propagationMethod' => craft\enums\PropagationMethod::from($context['propagation'] ?? 'all'),
    'enableVersioning' => true,
]);
$section->setEntryTypes([$app->getEntries()->getEntryTypeById($f['typeId'])]);
$section->setSiteSettings(array_map(fn($site) => new craft\models\Section_SiteSettings(['siteId' => $site->id, 'enabledByDefault' => false, 'hasUrls' => false]), $app->getSites()->getAllSites()));
if (!$app->getEntries()->saveSection($section)) {
    throw new RuntimeException(json_encode($section->getErrors()));
}
foreach ($app->getUserGroups()->getAllGroups() as $group) {
    $permissions = $app->getUserPermissions()->getPermissionsByGroupId($group->id);
    $additional = [];
    foreach ($permissions as $permission) {
        if (str_contains($permission, ':' . $f['sectionUid'])) {
            $additional[] = str_replace($f['sectionUid'], $section->uid, $permission);
        }
    }
    $app->getUserPermissions()->saveGroupPermissions($group->id, array_merge($permissions, $additional));
}
$sectionId = $section->id;

$app->getProjectConfig()->saveModifiedConfigData();
