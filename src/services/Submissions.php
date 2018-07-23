<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\elements\User;

class Submissions extends Component
{
    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, Submission::class);
    }

    public function sendPublisherNotificationEmail($submission)
    {
        $settings = Workflow::$plugin->getSettings();

        $query = User::find()
            ->groupId($settings->publisherUserGroup);

        // Check settings to see if we should email all publishers or not
        if (isset($settings->selectedPublishers)) {
            if ($settings->selectedPublishers != '*') {
                $query->id($settings->selectedPublishers);
            }
        }

        $publishers = $query->all();

        foreach ($publishers as $key => $user) {
            Craft::$app->getMailer()
                ->composeFromKey('workflow_publisher_notification', ['submission' => $submission])
                ->setTo($user)
                ->send();
        }
    }

    public function sendEditorNotificationEmail($submission)
    {
        $settings = Workflow::$plugin->getSettings();

        $editor = User::find()
            ->groupId($settings->editorUserGroup)
            ->id($submission->editorId)
            ->one();

        // Only send to the single user editor - not the whole group
        if ($editor) {
            Craft::$app->getMailer()
                ->composeFromKey('workflow_editor_notification', ['submission' => $submission])
                ->setTo($editor)
                ->send();
        }
    }
}
