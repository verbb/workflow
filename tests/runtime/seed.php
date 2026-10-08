<?php
require __DIR__ . '/verify.php';

use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\models\UserGroup;

function saved(bool $success, $model): void {
    if (!$success) {
        throw new RuntimeException(json_encode($model->getErrors()));
    }
}

$sites = Craft::$app->getSites();
$primary = $sites->getPrimarySite();
$secondary = new Site(['groupId' => $primary->groupId, 'name' => 'French', 'handle' => 'french', 'language' => 'fr', 'hasUrls' => false]);
saved($sites->saveSite($secondary), $secondary);
$sites->refreshSites();
Craft::$app->getIsMultiSite(true);
Craft::$app->getIsMultiSite(true, true);
$field = new PlainText(['name' => 'Summary', 'handle' => 'summary']);
saved(Craft::$app->getFields()->saveField($field), $field);
$localized = new PlainText(['name' => 'Localized summary', 'handle' => 'localizedSummary', 'translationMethod' => 'site']);
saved(Craft::$app->getFields()->saveField($localized), $localized);
$related = new craft\fields\Entries(['name' => 'Related entries', 'handle' => 'relatedEntries', 'sources' => '*']);
saved(Craft::$app->getFields()->saveField($related), $related);
$table = new craft\fields\Table(['name' => 'Details', 'handle' => 'details', 'columns' => ['col1' => ['handle' => 'label', 'heading' => 'Label', 'type' => 'singleline']]]);
saved(Craft::$app->getFields()->saveField($table), $table);
$blockLayout = new FieldLayout(['type' => Entry::class]);
$blockTab = new FieldLayoutTab(['name' => 'Content', 'layout' => $blockLayout]);
$blockTab->setElements([new EntryTitleField(), new CustomField($field)]);
$blockLayout->setTabs([$blockTab]);
$blockType = new EntryType(['name' => 'Text block', 'handle' => 'textBlock', 'hasTitleField' => true]);
$blockType->setFieldLayout($blockLayout);
saved(Craft::$app->getEntries()->saveEntryType($blockType), $blockType);
$matrix = new craft\fields\Matrix(['name' => 'Body', 'handle' => 'body']);
$matrix->setEntryTypes([$blockType]);
saved(Craft::$app->getFields()->saveField($matrix), $matrix);
$layout = new FieldLayout(['type' => Entry::class]);
$tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $layout]);
$tab->setElements([new EntryTitleField(), new CustomField($field, ['required' => true]), new CustomField($localized), new CustomField($related), new CustomField($table), new CustomField($matrix)]);
$layout->setTabs([$tab]);
$type = new EntryType(['name' => 'Article', 'handle' => 'article', 'hasTitleField' => true]);
$type->setFieldLayout($layout);
saved(Craft::$app->getEntries()->saveEntryType($type), $type);
$section = new Section(['name' => 'Articles', 'handle' => 'articles', 'type' => Section::TYPE_CHANNEL, 'enableVersioning' => true]);
$section->setEntryTypes([$type]);
$section->setSiteSettings(array_map(fn($site) => new Section_SiteSettings(['siteId' => $site->id, 'enabledByDefault' => false, 'hasUrls' => false]), [$primary, $secondary]));
saved(Craft::$app->getEntries()->saveSection($section), $section);
$groups = [];
foreach (['editors', 'publishers', 'outsiders', 'reviewersOne', 'reviewersTwo', 'secondaryPublishers'] as $handle) {
    $group = new UserGroup(['name' => ucfirst($handle), 'handle' => $handle]);
    saved(Craft::$app->getUserGroups()->saveGroup($group), $group);
    $permissions = ['accessCp', 'accessPlugin-workflow', 'workflow-overview'];
    foreach (['viewEntries', 'viewPeerEntries', 'createEntries', 'saveEntries', 'savePeerEntries', 'viewPeerEntryDrafts', 'savePeerEntryDrafts', 'deletePeerEntryDrafts'] as $permission) {
        if ($handle === 'editors' && in_array($permission, ['saveEntries', 'savePeerEntries', 'savePeerEntryDrafts', 'deletePeerEntryDrafts'])) {
            continue;
        }
        $permissions[] = "$permission:$section->uid";
    }
    foreach ([$primary, $secondary] as $site) {
        if ($handle === 'secondaryPublishers' && $site->id === $primary->id) {
            continue;
        }
        $permissions[] = "editSite:$site->uid";
    }
    if (!Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions)) {
        throw new RuntimeException('Cannot save fixture permissions.');
    }
    $groups[$handle] = $group;
}
$userIds = [];
foreach (['editor' => ['editors'], 'canonicalEditor' => ['editors', 'outsiders'], 'editorTwo' => ['editors'], 'reviewerOne' => ['reviewersOne'], 'reviewerTwo' => ['reviewersTwo'], 'secondaryPublisher' => ['secondaryPublishers'], 'publisher' => ['publishers'], 'selfPublisher' => ['editors', 'publishers'], 'outsider' => ['outsiders'], 'limitedPublisher' => ['publishers']] as $name => $memberships) {
    $user = new User(['username' => $name, 'email' => "$name@example.test", 'active' => true]);
    $user->newPassword = 'Workflow-testing-password-815!';
    saved(Craft::$app->getElements()->saveElement($user), $user);
    Craft::$app->getUsers()->assignUserToGroups($user->id, array_map(fn($handle) => $groups[$handle]->id, $memberships));
    $userIds[$name] = $user->id;
}
// This publisher has a Workflow role but no Craft entry permissions.
Craft::$app->getUsers()->assignUserToGroups($userIds['limitedPublisher'], []);
$limited = new UserGroup(['name' => 'Limited publishers', 'handle' => 'limitedPublishers']);
saved(Craft::$app->getUserGroups()->saveGroup($limited), $limited);
Craft::$app->getUsers()->assignUserToGroups($userIds['limitedPublisher'], [$limited->id]);

$settings = ['enabledSections' => [$section->uid], 'editorNotifications' => false, 'publisherNotifications' => false, 'reviewerNotifications' => false, 'publishedAuthorNotifications' => false];
foreach ([$primary, $secondary] as $site) {
    $settings['editorUserGroup'][$site->uid] = $groups['editors']->uid;
    $settings['publisherUserGroup'][$site->uid] = $groups['publishers']->uid;
}
saved(Craft::$app->getPlugins()->savePluginSettings(verbb\workflow\Workflow::$plugin, $settings), verbb\workflow\Workflow::$plugin->getSettings());
$userIds['admin'] = User::find()->admin(true)->one()->id;
file_put_contents(CRAFT_BASE_PATH . '/../fixtures.json', json_encode(['sectionId' => $section->id, 'typeId' => $type->id, 'sites' => [$primary->id, $secondary->id], 'users' => $userIds, 'limitedGroupUid' => $limited->uid, 'matrixFieldId' => $matrix->id, 'blockTypeId' => $blockType->id, 'sectionUid' => $section->uid, 'groups' => array_map(fn($group) => $group->uid, $groups)]));
Craft::$app->getProjectConfig()->saveModifiedConfigData();

// Public browser fixtures share this owned installation and normal Craft authentication.
$templates = CRAFT_BASE_PATH . '/templates';
craft\helpers\FileHelper::createDirectory($templates);
foreach (glob(dirname(__DIR__) . '/_craft/templates/*.twig') as $source) {
    copy($source, $templates . '/' . basename($source));
}
craft\helpers\FileHelper::createDirectory(CRAFT_WEB_ROOT . '/cpresources');
$publicAssets = __DIR__ . '/web/cpresources';
if (!file_exists($publicAssets) && !is_link($publicAssets)) {
    symlink('../../../.cache/verbb-tests/app/web/cpresources', $publicAssets);
}
