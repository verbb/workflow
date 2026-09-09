<?php
namespace verbb\workflow\models;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\models\Site;

class Settings extends Model
{
    // Properties
    // =========================================================================

    // General
    public string $pluginName = 'Workflow';
    public array $editorUserGroup = [];
    public array $reviewerUserGroups = [];
    public array $publisherUserGroup = [];
    public array $editorNotesRequired = [];
    public array $publisherNotesRequired = [];
    public bool $lockDraftSubmissions = true;
    public array $publisherSelfApprovalUserGroups = [];

    // Notifications
    public bool $editorNotifications = true;
    public array $editorNotificationsOptions = [];
    public bool $reviewerNotifications = true;
    public bool $reviewerApprovalNotifications = false;
    public bool $publisherNotifications = true;
    public bool $publishedAuthorNotifications = false;
    public ?string $publisherNotificationsUserGroup = null;

    // Permissions
    public mixed $enabledSections = '*';

    // Deprecated
    public mixed $selectedPublishers = '*';


    // Public Methods
    // =========================================================================

    public function getEditorUserGroup($site)
    {
        $groupUid = $this->editorUserGroup[$site->uid] ?? null;

        if ($groupUid) {
            return Craft::$app->getUserGroups()->getGroupByUid($groupUid);
        }

        return null;
    }

    public function getEditorUserGroupUid($site)
    {
        return $this->getEditorUserGroup($site)->uid ?? null;
    }

    public function getReviewerUserGroups($site)
    {
        $userGroups = [];
        $siteGroups = $this->reviewerUserGroups[$site->uid] ?? [];

        // For when no items are passed, this will be a string
        if (!is_array($siteGroups)) {
            $siteGroups = [];
        }

        foreach ($siteGroups as $siteGroup) {
            // Get UID from first element in array
            $uid = $siteGroup[0] ?? null;

            if ($uid === null) {
                continue;
            }

            $userGroup = Craft::$app->getUserGroups()->getGroupByUid($uid);

            if ($userGroup !== null) {
                $userGroups[] = $userGroup;
            }
        }

        return $userGroups;
    }

    public function getReviewerUserGroupsUids($site)
    {
        $uids = [];

        foreach (ArrayHelper::getColumn($this->getReviewerUserGroups($site), 'uid') as $value) {
            $uids[] = [$value];
        }

        return $uids;
    }

    public function getPublisherUserGroup($site)
    {
        $groupUid = $this->publisherUserGroup[$site->uid] ?? null;

        if ($groupUid) {
            return Craft::$app->getUserGroups()->getGroupByUid($groupUid);
        }

        return null;
    }

    public function getPublisherUserGroupUid($site)
    {
        return $this->getPublisherUserGroup($site)->uid ?? null;
    }

    public function getEditorNotesRequired($site)
    {
        return $this->editorNotesRequired[$site->uid] ?? false;
    }

    public function getPublisherNotesRequired($site)
    {
        return $this->publisherNotesRequired[$site->uid] ?? false;
    }

    public function getPublishersForNotificationEmail($site): array
    {
        // Get the user group for email notifications
        if ($this->publisherNotificationsUserGroup) {
            $publisherGroup = Craft::$app->getUserGroups()->getGroupByUid($this->publisherNotificationsUserGroup);
        }

        $publisherGroup = $publisherGroup ?? $this->getPublisherUserGroup($site);

        if (!$publisherGroup) {
            return [];
        }

        return User::find()->groupId($publisherGroup->id)->all();
    }

    public function getPublisherSelfApprovalUserGroups(Site $site): array
    {
        $siteGroups = $this->publisherSelfApprovalUserGroups[$site->uid] ?? [];

        if (!is_array($siteGroups)) {
            return [];
        }

        $userGroups = [];

        foreach ($siteGroups as $siteGroup) {
            $uid = $siteGroup[0] ?? null;

            if ($uid === null || $uid === '') {
                continue;
            }

            $userGroup = Craft::$app->getUserGroups()->getGroupByUid($uid);

            if ($userGroup !== null) {
                $userGroups[] = $userGroup;
            }
        }

        return $userGroups;
    }

    public function getPublisherSelfApprovalUserGroupsUids(Site $site): array
    {
        $uids = [];

        foreach (ArrayHelper::getColumn($this->getPublisherSelfApprovalUserGroups($site), 'uid') as $value) {
            $uids[] = [$value];
        }

        return $uids;
    }

    public function userMatchesPublisherSelfApprovalBypassGroups(User $user, Site $site): bool
    {
        foreach ($this->getPublisherSelfApprovalUserGroups($site) as $userGroup) {
            if ($user->isInGroup($userGroup)) {
                return true;
            }
        }

        return false;
    }

    public function getUserStatuses(User $user, $site): array
    {
        $statuses = Review::statuses();

        $publisherGroup = $this->getPublisherUserGroup($site);

        if ($user->isInGroup($publisherGroup)) {
            return $statuses;
        }

        foreach ($this->getReviewerUserGroups($site) as $userGroup) {
            if ($user->isInGroup($userGroup)) {
                return $statuses;
            }
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            return $statuses;
        }

        unset($statuses['approved'], $statuses['rejected']);

        return $statuses;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName'], 'trim'];
        $rules[] = [['pluginName'], 'required'];

        return $rules;
    }
}
