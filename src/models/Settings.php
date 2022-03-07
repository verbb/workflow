<?php
namespace verbb\workflow\models;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    // General
    public mixed $editorUserGroup;
    public array $reviewerUserGroups = [];
    public mixed $publisherUserGroup;
    public bool $editorNotesRequired = false;
    public bool $publisherNotesRequired = false;
    public bool $lockDraftSubmissions = true;

    // Notifications
    public bool $editorNotifications = true;
    public array $editorNotificationsOptions = [];
    public bool $reviewerNotifications = true;
    public bool $reviewerApprovalNotifications = false;
    public bool $publisherNotifications = true;
    public mixed $selectedPublishers = '*';

    // Permissions
    public mixed $enabledSections = '*';


    // Public Methods
    // =========================================================================

    public function getReviewerUserGroups(): array
    {
        // Protect against _somehow_ this not being an array...
        if (!is_array($this->reviewerUserGroups)) {
            return [];
        }

        return $this->reviewerUserGroups;
    }
}
