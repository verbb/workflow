<?php
namespace verbb\workflow\models;

use craft\base\Model;

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

    public function getReviewerUserGroups()
    {
        // Protect against _somehow_ this not being an array...
        if (!is_array($this->reviewerUserGroups)) {
            return [];
        }

        return $this->reviewerUserGroups;
    }
}
