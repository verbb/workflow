/** Seed a current Craft entry, draft and genuine Workflow review history. */

use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Lightswitch;
use craft\fields\PlainText;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\UserGroup;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;
use verbb\workflow\Workflow;

Craft::$app->setEdition(CmsEdition::Pro);

$elements = Craft::$app->getElements();
$entries = Craft::$app->getEntries();
$fields = Craft::$app->getFields();
$users = Craft::$app->getUsers();
$userGroups = Craft::$app->getUserGroups();
$site = Craft::$app->getSites()->getPrimarySite();

$createUser = static function(string $username, string $email, string $firstName, string $lastName) use ($elements): User {
    $user = User::find()->email($email)->status(null)->one();

    if ($user) {
        return $user;
    }

    $user = new User([
        'username' => $username,
        'email' => $email,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'active' => true,
        'newPassword' => 'screenshot-password',
    ]);
    $user->setScenario(User::SCENARIO_REGISTRATION);

    if (!$elements->saveElement($user)) {
        throw new RuntimeException('Unable to save Workflow screenshot user: ' . Json::encode($user->getErrors()));
    }

    return $user;
};

$createGroup = static function(string $name, string $handle) use ($userGroups): UserGroup {
    $group = $userGroups->getGroupByHandle($handle);

    if ($group) {
        return $group;
    }

    $group = new UserGroup(['name' => $name, 'handle' => $handle]);

    if (!$userGroups->saveGroup($group)) {
        throw new RuntimeException('Unable to save Workflow screenshot group: ' . Json::encode($group->getErrors()));
    }

    return $group;
};

$admin = User::find()->admin(true)->status(null)->one();

if (!$admin) {
    throw new RuntimeException('Unable to find the shared screenshot administrator.');
}

$editor = $createUser('amelia.hart', 'amelia@example.test', 'Amelia', 'Hart');
$reviewer = $createUser('noah.bennett', 'noah@example.test', 'Noah', 'Bennett');
$editorsGroup = $createGroup('Editors', 'workflowEditors');
$reviewersGroup = $createGroup('Senior reviewers', 'workflowReviewers');
$publishersGroup = $createGroup('Publishers', 'workflowPublishers');

$users->assignUserToGroups($editor->id, [$editorsGroup->id]);
$users->assignUserToGroups($reviewer->id, [$reviewersGroup->id]);
$users->assignUserToGroups($admin->id, [$publishersGroup->id]);

$summaryField = $fields->getFieldByHandle('workflowSummary');

if (!$summaryField instanceof PlainText) {
    $summaryField = new PlainText([
        'name' => 'Summary',
        'handle' => 'workflowSummary',
        'multiline' => true,
        'initialRows' => 4,
    ]);

    if (!$fields->saveField($summaryField)) {
        throw new RuntimeException('Unable to save Workflow Summary field: ' . Json::encode($summaryField->getErrors()));
    }
}

$callToActionField = $fields->getFieldByHandle('workflowCallToAction');

if (!$callToActionField instanceof PlainText) {
    $callToActionField = new PlainText([
        'name' => 'Call to action',
        'handle' => 'workflowCallToAction',
        'multiline' => false,
    ]);

    if (!$fields->saveField($callToActionField)) {
        throw new RuntimeException('Unable to save Workflow Call to action field: ' . Json::encode($callToActionField->getErrors()));
    }
}

$featuredField = $fields->getFieldByHandle('workflowFeatured');

if (!$featuredField instanceof Lightswitch) {
    $featuredField = new Lightswitch([
        'name' => 'Feature on homepage',
        'handle' => 'workflowFeatured',
        'default' => false,
    ]);

    if (!$fields->saveField($featuredField)) {
        throw new RuntimeException('Unable to save Workflow Featured field: ' . Json::encode($featuredField->getErrors()));
    }
}

$section = $entries->getSectionByHandle('workflowArticles');

if (!$section) {
    $entryType = new EntryType([
        'name' => 'Article',
        'handle' => 'workflowArticle',
        'hasTitleField' => true,
    ]);

    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $layout]);
    $tab->setElements([
        new EntryTitleField(),
        new CustomField($summaryField),
        new CustomField($callToActionField),
        new CustomField($featuredField),
    ]);
    $layout->setTabs([$tab]);
    $entryType->setFieldLayout($layout);

    if (!$entries->saveEntryType($entryType)) {
        throw new RuntimeException('Unable to save Workflow screenshot entry type: ' . Json::encode($entryType->getErrors()));
    }

    $section = new Section([
        'name' => 'Articles',
        'handle' => 'workflowArticles',
        'type' => Section::TYPE_CHANNEL,
    ]);
    $section->setEntryTypes([$entryType]);
    $section->setSiteSettings([
        new Section_SiteSettings([
            'siteId' => $site->id,
            'enabledByDefault' => true,
            'hasUrls' => false,
        ]),
    ]);

    if (!$entries->saveSection($section)) {
        throw new RuntimeException('Unable to save Workflow screenshot section: ' . Json::encode($section->getErrors()));
    }
}

$entryType = $entries->getEntryTypesBySectionId($section->id)[0] ?? null;

if (!$entryType) {
    throw new RuntimeException('Workflow screenshot section has no entry type.');
}

$entry = Entry::find()
    ->sectionId($section->id)
    ->slug('spring-campaign')
    ->siteId($site->id)
    ->status(null)
    ->one();

if (!$entry) {
    $entry = new Entry([
        'sectionId' => $section->id,
        'typeId' => $entryType->id,
        'siteId' => $site->id,
        'slug' => 'spring-campaign',
        'enabled' => true,
    ]);
}

$entry->title = 'Spring campaign overview';
$entry->authorId = $editor->id;
$entry->postDate = new DateTime('2026-09-10 16:00:00', new DateTimeZone('UTC'));
$entry->setFieldValue('workflowSummary', 'An early outline of the spring campaign, ready for the editorial team to shape before launch.');
$entry->setFieldValue('workflowCallToAction', 'Learn more');
$entry->setFieldValue('workflowFeatured', false);

if (!$elements->saveElement($entry)) {
    throw new RuntimeException('Unable to save Workflow screenshot entry: ' . Json::encode($entry->getErrors()));
}

$oldRevisionData = Workflow::$plugin->getContent()->getRevisionData($entry);
$draft = Craft::$app->getDrafts()->createDraft($entry, $editor->id, 'Spring campaign review', 'Updated after editorial feedback.');
$draft->title = 'Spring campaign launch';
$draft->slug = 'spring-campaign-launch';
$draft->setFieldValue('workflowSummary', 'Meet the makers behind our spring collection and discover the materials, ideas and local partnerships that brought it to life.');
$draft->setFieldValue('workflowCallToAction', 'Explore the collection');
$draft->setFieldValue('workflowFeatured', true);

if (!$elements->saveElement($draft)) {
    throw new RuntimeException('Unable to save Workflow screenshot draft: ' . Json::encode($draft->getErrors()));
}

$plugin = Craft::$app->getPlugins()->getPlugin('workflow');

if (!$plugin) {
    throw new RuntimeException('Workflow is not installed.');
}

if (!Craft::$app->getPlugins()->savePluginSettings($plugin, [
    'editorUserGroup' => [$site->uid => $editorsGroup->uid],
    'reviewerUserGroups' => [$site->uid => [[$reviewersGroup->uid]]],
    'publisherUserGroup' => [$site->uid => $publishersGroup->uid],
    'enabledSections' => '*',
    'editorNotifications' => false,
    'reviewerNotifications' => false,
    'publisherNotifications' => false,
])) {
    throw new RuntimeException('Unable to save Workflow screenshot settings.');
}

$projectConfig = Craft::$app->getProjectConfig();
$projectConfig->saveModifiedConfigData();
$projectConfig->writeYamlFiles(true);

$savedSettings = ProjectConfigHelper::unpackAssociativeArrays(
    $projectConfig->get('plugins.workflow.settings') ?? [],
);

if (
    ($savedSettings['publisherUserGroup'][$site->uid] ?? null) !== $publishersGroup->uid ||
    ($savedSettings['editorUserGroup'][$site->uid] ?? null) !== $editorsGroup->uid
) {
    throw new RuntimeException('Workflow screenshot settings were not persisted: ' . Json::encode($savedSettings));
}

$submission = new Submission([
    'siteId' => $site->id,
    'ownerId' => $entry->id,
    'ownerSiteId' => $site->id,
    'isComplete' => false,
    'isPending' => true,
]);

if (!$elements->saveElement($submission)) {
    throw new RuntimeException('Unable to save Workflow screenshot submission: ' . Json::encode($submission->getErrors()));
}

Db::update('{{%workflow_submissions}}', [
    'dateCreated' => '2026-09-15 09:15:00',
    'dateUpdated' => '2026-09-16 10:15:00',
], ['id' => $submission->id]);

$saveReview = static function(
    int $userId,
    string $role,
    string $status,
    string $notes,
    array $data,
    string $dateCreated
) use ($submission, $entry, $draft): Review {
    $review = new Review([
        'submissionId' => $submission->id,
        'elementId' => $entry->id,
        'elementSiteId' => $entry->siteId,
        'draftId' => $draft->draftId,
        'userId' => $userId,
        'role' => $role,
        'status' => $status,
        'data' => $data,
    ]);
    $review->setNotes($notes);

    if (!Workflow::$plugin->getReviews()->saveReview($review)) {
        throw new RuntimeException('Unable to save Workflow screenshot review: ' . Json::encode($review->getErrors()));
    }

    Db::update('{{%workflow_reviews}}', [
        'dateCreated' => $dateCreated,
        'dateUpdated' => $dateCreated,
    ], ['id' => $review->id]);

    return $review;
};

$firstReview = $saveReview(
    $editor->id,
    Review::ROLE_EDITOR,
    Review::STATUS_PENDING,
    'The campaign outline is ready for an editorial pass.',
    $oldRevisionData,
    '2026-09-15 09:20:00'
);
$rejectedReview = $saveReview(
    $reviewer->id,
    Review::ROLE_REVIEWER,
    Review::STATUS_REJECTED,
    'Please sharpen the opening and make the next step clearer for readers.',
    $oldRevisionData,
    '2026-09-15 14:40:00'
);
$latestReview = $saveReview(
    $editor->id,
    Review::ROLE_EDITOR,
    Review::STATUS_PENDING,
    'Updated the introduction, call to action and homepage placement.',
    Workflow::$plugin->getContent()->getRevisionData($draft),
    '2026-09-16 10:15:00'
);

$submission->clearReviews();
Craft::$app->getCache()->flush();

$draftUrl = parse_url((string)$draft->getCpEditUrl());
$draftRoute = ($draftUrl['path'] ?? '') . (isset($draftUrl['query']) ? '?' . $draftUrl['query'] : '');

echo Json::encode([
    'draftRoute' => $draftRoute,
    'submissionRoute' => '/admin/workflow/submissions/edit/' . $submission->id,
    'compareRoute' => '/admin/workflow/reviews/compare/' . $latestReview->id . ':' . $rejectedReview->id,
    'reviewCount' => 3,
], JSON_THROW_ON_ERROR);
