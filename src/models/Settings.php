<?php
namespace verbb\workflow\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    // General
    public $editorUserGroup;
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

}
