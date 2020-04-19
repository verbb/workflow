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
    public $reviewerNotifications = true;
    public $publisherNotifications = true;
    public $selectedPublishers = '*';

    // Permissions
    public $enabledSections = '*';

    // Public Methods
    // =========================================================================

    /**
     * Returns the reviewer user groups.
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
}
