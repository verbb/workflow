<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SEND_EDITOR_EMAIL = 'beforeSendEditorEmail';
    const EVENT_BEFORE_SEND_PUBLISHER_EMAIL = 'beforeSendPublisherEmail';

    
    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, Submission::class);
    }

    public function sendPublisherNotificationEmail($submission)
    {
        $settings = Workflow::$plugin->getSettings();

        $groupId = Db::idByUid(Table::USERGROUPS, $settings->publisherUserGroup);

        $query = User::find()
            ->groupId($groupId);

        // Check settings to see if we should email all publishers or not
        if (isset($settings->selectedPublishers)) {
            if ($settings->selectedPublishers != '*') {
                $query->id($settings->selectedPublishers);
            }
        }

        $publishers = $query->all();

        foreach ($publishers as $key => $user) {
            try {
                $mail = Craft::$app->getMailer()
                    ->composeFromKey('workflow_publisher_notification', ['submission' => $submission])
                    ->setTo($user);

                // Fire a 'beforeSendPublisherEmail' event
                if ($this->hasEventHandlers(self::EVENT_BEFORE_SEND_PUBLISHER_EMAIL)) {
                    $this->trigger(self::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, new EmailEvent([
                        'mail' => $mail,
                        'user' => $user,
                    ]));
                }

                $mail->send();

                Workflow::log('Sent publisher notification to ' . $user->email);
            } catch (\Throwable $e) {
                Workflow::log('Failed to send publisher notification to ' . $user->email . ' - ' . $e->getMessage());
            }
        }
    }

    public function sendEditorNotificationEmail($submission)
    {
        $settings = Workflow::$plugin->getSettings();

        $groupId = Db::idByUid(Table::USERGROUPS, $settings->editorUserGroup);

        $editor = User::find()
            ->groupId($groupId)
            ->id($submission->editorId)
            ->one();

        // Only send to the single user editor - not the whole group
        if ($editor) {
            try {
                $mail = Craft::$app->getMailer()
                    ->composeFromKey('workflow_editor_notification', ['submission' => $submission])
                    ->setTo($editor);

                if (!is_array($settings->editorNotificationsOptions)) {
                    $settings->editorNotificationsOptions = [];
                }

                if ($submission->publisher) {
                    if (in_array('replyTo', $settings->editorNotificationsOptions)) {
                        $mail->setReplyTo($submission->publisher->email);
                    }

                    if (in_array('cc', $settings->editorNotificationsOptions)) {
                        $mail->setCc($submission->publisher->email);
                    }
                }

                // Fire a 'beforeSendEditorEmail' event
                if ($this->hasEventHandlers(self::EVENT_BEFORE_SEND_EDITOR_EMAIL)) {
                    $this->trigger(self::EVENT_BEFORE_SEND_EDITOR_EMAIL, new EmailEvent([
                        'mail' => $mail,
                        'user' => $editor,
                    ]));
                }

                $mail->send();

                Workflow::log('Sent editor notification to ' . $editor->email);
            } catch (\Throwable $e) {
                Workflow::log('Failed to send editor notification to ' . $editor->email . ' - ' . $e->getMessage());
            }
        }
    }
}
