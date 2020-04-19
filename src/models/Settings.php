<?php
namespace verbb\workflow\models;

use Craft;
use craft\base\Model;
use craft\models\UserGroup;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    // General
    public $editorUserGroup;
    public $reviewerUserGroups = [];
    public $publisherUserGroup;
    public $editorNotesRequired = false;
    public $publisherNotesRequired = false;

    // Notifications
    public $editorNotifications = true;
    public $editorNotificationsOptions = [];
    public $publisherNotifications = true;
    public $selectedPublishers = '*';

    // Permissions
    public $enabledSections = '*';

    // Public Methods
    // =========================================================================

    /**
     * Returns an array of user groups that are editors in the multi-step approval process.
     *
     * @return UserGroup[]
     */
    public function getReviewerUserGroups(): array
    {
        $userGroups = [];

        foreach ($this->reviewerUserGroups as $reviewerUserGroup) {
            // Get UID from first element in array
            $uid = $reviewerUserGroup[0] ?? null;

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

    /**
     * Returns an array of approval steps with user group IDs as keys.
     *
     * @return array
     */
    public function getUserGroupApprovalSteps(): array
    {
        $approvalSteps = [];
        $count = 0;

        foreach ($this->getEditorUserGroups() as $userGroup) {
            $count++;
            $approvalSteps[$userGroup->id] = $count;
        }

        return $approvalSteps;
    }
}
