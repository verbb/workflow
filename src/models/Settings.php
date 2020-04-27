<?php
namespace verbb\workflow\models;

use Craft;
use craft\base\Model;
use craft\models\UserGroup;
use verbb\workflow\events\ReviewerUserGroupsEvent;
use yii\base\Event;

class Settings extends Model
{
    // Constants
    // =========================================================================

    const EVENT_AFTER_GET_REVIEWER_USER_GROUPS = 'afterGetReviewerUserGroups';

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

        // Fire an 'afterGetReviewerUserGroups' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_GET_REVIEWER_USER_GROUPS)) {
            $this->trigger(self::EVENT_AFTER_GET_REVIEWER_USER_GROUPS,
                new ReviewerUserGroupsEvent(['userGroups' => $userGroups])
            );
        }

        return $userGroups;
    }
}
