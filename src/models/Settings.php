<?php
namespace verbb\workflow\models;

use craft\base\Model;
use craft\helpers\Json;

class Settings extends Model
{
    // Properties
    // =========================================================================

    // General
    public mixed $editorUserGroup = null;
    public array $reviewerUserGroups = [];
    public mixed $publisherUserGroup = null;
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

    public function setAttributes($values, $safeOnly = true): void
    {
        // Config normalization
        if (array_key_exists('reviewerUserGroups', $values)) {
            if (is_string($values['reviewerUserGroups'])) {
                $values['reviewerUserGroups'] = Json::decodeIfJson($values['reviewerUserGroups']);
            }

            if (!is_array($values['reviewerUserGroups'])) {
                $values['reviewerUserGroups'] = [];
            }
        }

        if (array_key_exists('editorNotificationsOptions', $values)) {
            if (is_string($values['editorNotificationsOptions'])) {
                $values['editorNotificationsOptions'] = Json::decodeIfJson($values['editorNotificationsOptions']);
            }

            if (!is_array($values['editorNotificationsOptions'])) {
                $values['editorNotificationsOptions'] = [];
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function getReviewerUserGroups(): array
    {
        // Protect against _somehow_ this not being an array...
        if (!is_array($this->reviewerUserGroups)) {
            return [];
        }

        return $this->reviewerUserGroups;
    }
}
