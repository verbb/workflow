<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\EmailEvent;
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\events\ReviewerUserGroupsEvent;
use verbb\workflow\models\Review;

use Craft;
use craft\base\ElementInterface;
use craft\base\Component;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\UserGroup;

use Exception;
use Throwable;
use DateTime;

class Emails extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_PREPARE_EDITOR_EMAIL = 'prepareEditorEmail';
    public const EVENT_PREPARE_REVIEWER_EMAIL = 'prepareReviewerEmail';
    public const EVENT_PREPARE_PUBLISHER_EMAIL = 'preparePublisherEmail';
    public const EVENT_BEFORE_SEND_EDITOR_EMAIL = 'beforeSendEditorEmail';
    public const EVENT_BEFORE_SEND_REVIEWER_EMAIL = 'beforeSendReviewerEmail';
    public const EVENT_BEFORE_SEND_PUBLISHER_EMAIL = 'beforeSendPublisherEmail';


    // Public Methods
    // =========================================================================

    public function sendReviewerNotificationEmail(Submission $submission, Review $review, ElementInterface $entry): void
    {
        Workflow::log('Preparing reviewer notification.');

        $reviewerUserGroup = Workflow::$plugin->getSubmissions()->getNextReviewerUserGroup($submission, $entry);

        // If there is no next reviewer user group then send publisher notification email
        if ($reviewerUserGroup === null) {
            Workflow::log('No reviewer user groups. Send publisher email.');

            Workflow::$plugin->getEmails()->sendPublisherNotificationEmail($submission, $review, $entry);

            return;
        }

        $reviewers = User::find()
            ->groupId($reviewerUserGroup->id)
            ->all();

        // Fire a 'prepareReviewerEmail' event
        $event = new PrepareEmailEvent([
            'reviewers' => $reviewers,
            'submission' => $submission,
            'review' => $review,
        ]);
        $this->trigger(self::EVENT_PREPARE_REVIEWER_EMAIL, $event);

        if (!$event->isValid) {
            Workflow::log('Reviewer notification was cancelled by event.');
            return;
        }

        // Update the users from the event, potentially modified
        $reviewers = $event->reviewers;

        if (!$reviewers) {
            Workflow::log('No reviewers found to send notifications to.');
        }

        foreach ($reviewers as $key => $user) {
            try {
                $mail = Craft::$app->getMailer()
                    ->composeFromKey('workflow_publisher_notification', ['submission' => $submission, 'review' => $review])
                    ->setTo($user);

                // Fire a 'beforeSendReviewerEmail' event
                $event = new EmailEvent([
                    'mail' => $mail,
                    'user' => $user,
                    'submission' => $submission,
                    'review' => $review,
                ]);
                $this->trigger(self::EVENT_BEFORE_SEND_REVIEWER_EMAIL, $event);

                if (!$event->isValid) {
                    Workflow::log('Reviewer notification was cancelled by event.');
                    continue;
                }

                $event->mail->send();

                Workflow::log('Sent reviewer notification to ' . $event->user->email);
            } catch (Throwable $e) {
                Workflow::error(Craft::t('workflow', 'Failed to send reviewer notification to {value} - “{message}” {file}:{line}', [
                    'value' => $user->email,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            }
        }
    }

    public function sendPublisherNotificationEmail(Submission $submission, Review $review, ElementInterface $entry): void
    {
        Workflow::log('Preparing publisher notification.');

        $settings = Workflow::$plugin->getSettings();

        $publisherGroup = $settings->getPublisherUserGroup($entry->site);

        if (!$publisherGroup) {
            Workflow::log('No publisher group found to send notifications to.');
        }

        $query = User::find()->groupId($publisherGroup->id);

        // Check settings to see if we should email all publishers or not
        if (isset($settings->selectedPublishers) && $settings->selectedPublishers != '*') {
            $query->id($settings->selectedPublishers);
        }

        $publishers = $query->all();

        // Fire a 'preparePublisherEmail' event
        $event = new PrepareEmailEvent([
            'publishers' => $publishers,
            'submission' => $submission,
            'review' => $review,
        ]);
        $this->trigger(self::EVENT_PREPARE_PUBLISHER_EMAIL, $event);

        if (!$event->isValid) {
            Workflow::log('Publisher notification was cancelled by event.');
            return;
        }

        // Update the users from the event, potentially modified
        $publishers = $event->publishers;

        if (!$publishers) {
            Workflow::log('No publishers found to send notifications to.');
        }

        foreach ($publishers as $key => $user) {
            try {
                $mail = Craft::$app->getMailer()
                    ->composeFromKey('workflow_publisher_notification', ['submission' => $submission, 'review' => $review])
                    ->setTo($user);

                // Fire a 'beforeSendPublisherEmail' event
                $event = new EmailEvent([
                    'mail' => $mail,
                    'user' => $user,
                    'submission' => $submission,
                    'review' => $review,
                ]);
                $this->trigger(self::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, $event);

                if (!$event->isValid) {
                    Workflow::log('Publisher notification was cancelled by event.');
                    continue;
                }

                $event->mail->send();

                Workflow::log('Sent publisher notification to ' . $event->user->email);
            } catch (Throwable $e) {
                Workflow::error(Craft::t('workflow', 'Failed to send publisher notification to {value} - “{message}” {file}:{line}', [
                    'value' => $user->email,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            }
        }
    }

    public function sendEditorReviewNotificationEmail(Submission $submission, Review $review, ElementInterface $entry): void
    {
        $this->sendEditorNotificationEmail($submission, $review, $entry, true);
    }

    public function sendEditorNotificationEmail(Submission $submission, Review $review, ElementInterface $entry, bool $fromReviewer = false): void
    {
        Workflow::log('Preparing editor notification.');

        $settings = Workflow::$plugin->getSettings();

        if (!$submission->editorId) {
            Workflow::error('Unable to find editor for submission.');

            return;
        }

        $editor = User::find()
            ->id($submission->editorId)
            ->one();

        // Fire a 'prepareEditorEmail' event
        $event = new PrepareEmailEvent([
            'editor' => $editor,
            'submission' => $submission,
            'review' => $review,
        ]);
        $this->trigger(self::EVENT_PREPARE_EDITOR_EMAIL, $event);

        if (!$event->isValid) {
            Workflow::log('Editor notification was cancelled by event.');
            return;
        }

        // Update the user from the event, potentially modified
        $editor = $event->editor;

        // Only send to the single user editor - not the whole group
        if (!$editor) {
            Workflow::error('Unable to find editor #' . $submission->editorId);

            return;
        }

        try {
            $template = $fromReviewer ? 'workflow_editor_review_notification' : 'workflow_editor_notification';

            $mail = Craft::$app->getMailer()->composeFromKey($template, [
                'submission' => $submission,
                'review' => $review,
            ]);

            $mail->setTo($editor);

            if (!is_array($settings->editorNotificationsOptions)) {
                $settings->editorNotificationsOptions = [];
            }

            if ($fromReviewer) {
                $reviewer = $submission->getReviewer();

                if ($reviewer !== null) {
                    if (in_array('replyToReviewer', $settings->editorNotificationsOptions)) {
                        $mail->setReplyTo($reviewer->email);
                    }

                    if (in_array('ccReviewer', $settings->editorNotificationsOptions)) {
                        $mail->setCc($reviewer->email);
                    }
                }
            } else {
                if ($submission->publisher) {
                    if (in_array('replyTo', $settings->editorNotificationsOptions)) {
                        $mail->setReplyTo($submission->publisher->email);
                    }

                    if (in_array('cc', $settings->editorNotificationsOptions)) {
                        $mail->setCc($submission->publisher->email);
                    }
                }
            }

            // Fire a 'beforeSendEditorEmail' event
            $event = new EmailEvent([
                'mail' => $mail,
                'user' => $editor,
                'submission' => $submission,
                'review' => $review,
            ]);
            $this->trigger(self::EVENT_BEFORE_SEND_EDITOR_EMAIL, $event);

            if (!$event->isValid) {
                Workflow::log('Editor notification was cancelled by event.');
                return;
            }

            $event->mail->send();

            if ($fromReviewer) {
                Workflow::log('Sent editor review notification to ' . $event->user->email);
            } else {
                Workflow::log('Sent editor notification to ' . $event->user->email);
            }
        } catch (Throwable $e) {
            Workflow::error(Craft::t('workflow', 'Failed to send editor notification to {value} - “{message}” {file}:{line}', [
                'value' => $editor->email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }
    }

    public function sendPublishedAuthorNotificationEmail(Submission $submission, Review $review, ElementInterface $entry): void
    {
        Workflow::log('Preparing published author notification.');

        $settings = Workflow::$plugin->getSettings();

        // Fire a 'prepareEditorEmail' event
        $event = new PrepareEmailEvent([
            'submission' => $submission,
            'review' => $review,
        ]);
        $this->trigger(self::EVENT_PREPARE_EDITOR_EMAIL, $event);

        if (!$event->isValid) {
            Workflow::log('Published Author notification was cancelled by event.');
            return;
        }

        try {
            $mail = Craft::$app->getMailer()->composeFromKey('workflow_published_author_notification', [
                'submission' => $submission,
                'review' => $review,
            ]);

            $mail->setTo($entry->getAuthor());

            // Fire a 'beforeSendEditorEmail' event
            $event = new EmailEvent([
                'mail' => $mail,
                'user' => $entry->getAuthor(),
                'submission' => $submission,
                'review' => $review,
            ]);
            $this->trigger(self::EVENT_BEFORE_SEND_EDITOR_EMAIL, $event);

            if (!$event->isValid) {
                Workflow::log('Published Author notification was cancelled by event.');
                return;
            }

            $event->mail->send();

            Workflow::log('Sent published author notification to ' . $event->user->email);
        } catch (Throwable $e) {
            Workflow::error(Craft::t('workflow', 'Failed to send published author notification to {value} - “{message}” {file}:{line}', [
                'value' => $entry->getAuthor()->email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }
    }
}
