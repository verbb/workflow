<?php
namespace verbb\workflow\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $enabledSections = '*';
    public $editorUserGroup;
    public $publisherUserGroup;
    public $editorNotifications = true;
    public $publisherNotifications = true;
    public $selectedPublishers = '*';
    
}
