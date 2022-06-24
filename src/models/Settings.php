<?php
namespace verbb\workflow\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    // General
    public $editorUserGroup = [];
    public $reviewerUserGroups = [];
    public $publisherUserGroup = [];
    public $editorNotesRequired = [];
    public $publisherNotesRequired = [];
    public $lockDraftSubmissions = true;

    // Notifications
    public $editorNotifications = true;
    public $editorNotificationsOptions = [];
    public $reviewerNotifications = true;
    public $reviewerApprovalNotifications = false;
    public $publisherNotifications = true;
    public $selectedPublishers = '*';

    // Permissions
    public $enabledSections = '*';


    // Public Methods
    // =========================================================================

    public function getEditorUserGroup($site)
    {
        // Backward-compatibility support for config files, which won't be migrated
        $this->handleDeprecatedSetting('editorUserGroup', $site);

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
        // Check for deprecated syntax using non-site UIDs as key
        if (is_array($this->reviewerUserGroups) && isset($this->reviewerUserGroups[0])) {
            Craft::$app->getDeprecator()->log('Workflow', 'The `reviewerUserGroups` setting has been updated, and will cause a fatal error in Craft 4. Please review our [docs](https://verbb.io/craft-plugins/workflow/docs/get-started/configuration).');

            $uids = [];

            foreach ($this->reviewerUserGroups as $uid) {
                $uids[] = [$uid];
            }

            $this->reviewerUserGroups = [$site->uid => $uids];
        }

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
        // Backward-compatibility support for config files, which won't be migrated
        $this->handleDeprecatedSetting('publisherUserGroup', $site);

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
        // Backward-compatibility support for config files, which won't be migrated
        $this->handleDeprecatedSetting('editorNotesRequired', $site);

        return $this->editorNotesRequired[$site->uid] ?? false;
    }

    public function getPublisherNotesRequired($site)
    {
        // Backward-compatibility support for config files, which won't be migrated
        $this->handleDeprecatedSetting('publisherNotesRequired', $site);

        return $this->publisherNotesRequired[$site->uid] ?? false;
    }


    // Private Methods
    // =========================================================================

    private function handleDeprecatedSetting($property, $site)
    {
        if (!is_array($this->$property)) {
            Craft::$app->getDeprecator()->log('Workflow', 'The `' . $property . '` setting has been updated, and will cause a fatal error in Craft 4. Please review our [docs](https://verbb.io/craft-plugins/workflow/docs/get-started/configuration).');
        
            $this->$property = [$site->uid => $this->$property];
        }
    }
}
