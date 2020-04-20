<?php
namespace verbb\workflow\services;

use craft\models\UserGroup;
use verbb\workflow\models\Review;
use verbb\workflow\records\Review as ReviewRecord;
use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\EmailEvent;

use Craft;
use craft\base\Component;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Db;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SEND_EDITOR_EMAIL = 'beforeSendEditorEmail';
    const EVENT_BEFORE_SEND_REVIEWER_EMAIL = 'beforeSendReviewerEmail';
    const EVENT_BEFORE_SEND_PUBLISHER_EMAIL = 'beforeSendPublisherEmail';


    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id)
    {
        return Craft::$app->getElements()->getElementById($id, Submission::class);
    }

    /**
     * Returns the next reviewer user group for the given submission.
     *
     * @param Submission $submission
     * @return UserGroup|null
     */
    public function getNextReviewerUserGroup(Submission $submission)
    {
        $reviewerUserGroups = Workflow::$plugin->getSettings()->getReviewerUserGroups();

        $lastApprovedReview = $submission->getLastReview(true);

        if ($lastApprovedReview === null) {
            return $reviewerUserGroups[0] ?? null;
        }

        $reviewer = Craft::$app->getUsers()->getUserById($lastApprovedReview->userId);
        $nextUserGroup = null;

        foreach ($reviewerUserGroups as $key => $reviewerUserGroup) {
            if ($reviewer->isInGroup($reviewerUserGroup)) {
                $nextUserGroup = $reviewerUserGroups[$key + 1] ?? $nextUserGroup;
            }
        }

        return $nextUserGroup;
    }

    public function saveSubmission($entry = null)
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->ownerId = $entry->id;
        $submission->ownerSiteId = $entry->siteId;
        $submission->editorId = $currentUser->id;
        $submission->status = Submission::STATUS_PENDING;
        $submission->dateApproved = null;
        $submission->editorNotes = $request->getParam('editorNotes', $submission->editorNotes);
        $submission->publisherNotes = $request->getParam('publisherNotes', $submission->publisherNotes);

        if ($entry->draftId) {
            $submission->ownerDraftId = $entry->draftId;
        }

        $submission->data = $this->_getRevisionData($entry);

        $isNew = !$submission->id;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not submit for approval.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        if ($isNew) {
            // Trigger notification to reviewer
            if ($settings->reviewerNotifications) {
                $this->sendReviewerNotificationEmail($submission);
            }
        }

        $session->setNotice(Craft::t('workflow', 'Entry submitted for approval.'));
    }

    public function revokeSubmission()
    {
        $settings = Workflow::$plugin->getSettings();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REVOKED;
        $submission->dateRevoked = new \DateTime;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not revoke submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Submission revoked.'));
    }

    public function approveReview()
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        $reviewRecord = new ReviewRecord([
            'submissionId' => $submission->id,
            'userId' => $currentUser->id,
            'approved' => true,
            'notes' => $request->getParam('notes'),
        ]);

        if (!$reviewRecord->save()) {
            $session->setError(Craft::t('workflow', 'Could not approve submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        $review = Review::populateModel($reviewRecord);

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            $this->sendEditorNotificationEmail($submission, $review);
        }

        $session->setNotice(Craft::t('workflow', 'Submission approved.'));
    }

    public function rejectReview()
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        $reviewRecord = new ReviewRecord([
            'submissionId' => $submission->id,
            'userId' => $currentUser->id,
            'approved' => false,
            'notes' => $request->getParam('notes'),
        ]);

        if (!$reviewRecord->save()) {
            $session->setError(Craft::t('workflow', 'Could not reject submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        $review = Review::populateModel($reviewRecord);

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            $this->sendEditorNotificationEmail($submission, $review);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));
    }

    public function approveSubmission($entry = null)
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_APPROVED;
        $submission->publisherId = $currentUser->id;
        $submission->dateApproved = new \DateTime;
        $submission->editorNotes = $request->getParam('editorNotes', $submission->editorNotes);
        $submission->publisherNotes = $request->getParam('publisherNotes', $submission->publisherNotes);

        // Update the owner to be the newly published entry, and remove the ownerDraftId - it no longer exists!
        if ($entry) {
            $submission->ownerId = $entry->id;
            $submission->ownerDraftId = null;
        }

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve and publish.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            $this->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('workflow', 'Entry approved and published.'));
    }

    public function rejectSubmission()
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REJECTED;
        $submission->publisherId = $currentUser->id;
        $submission->dateRejected = new \DateTime;
        $submission->editorNotes = $request->getParam('editorNotes', $submission->editorNotes);
        $submission->publisherNotes = $request->getParam('publisherNotes', $submission->publisherNotes);

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not reject submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            $this->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));
    }

    public function sendReviewerNotificationEmail($submission)
    {
        $reviewerUserGroup = $this->getNextReviewerUserGroup($submission);

        // If there is no next reviewer user group then send publisher notification email
        if ($reviewerUserGroup === null) {
            $this->sendPublisherNotificationEmail($submission);

            return;
        }

        $reviewers = User::find()
            ->groupId($reviewerUserGroup->id)
            ->all();

        foreach ($reviewers as $key => $user) {
            try {
                $mail = Craft::$app->getMailer()
                    ->composeFromKey('workflow_reviewer_notification', ['submission' => $submission])
                    ->setTo($user);

                // Fire a 'beforeSendPublisherEmail' event
                if ($this->hasEventHandlers(self::EVENT_BEFORE_SEND_REVIEWER_EMAIL)) {
                    $this->trigger(self::EVENT_BEFORE_SEND_REVIEWER_EMAIL, new EmailEvent([
                        'mail' => $mail,
                        'user' => $user,
                    ]));
                }

                $mail->send();

                Workflow::log('Sent reviewer notification to ' . $user->email);
            } catch (\Throwable $e) {
                Workflow::log('Failed to send reviewer notification to ' . $user->email . ' - ' . $e->getMessage());
            }
        }
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

    public function sendEditorNotificationEmail($submission, $review = null)
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
                $mail = Craft::$app->getMailer()->setTo($editor);

                if ($review === null) {
                    $mail->composeFromKey('workflow_editor_notification', ['submission' => $submission]);
                }
                else {
                    $mail->composeFromKey('workflow_editor_review_notification', [
                        'submission' => $submission,
                        'review' => $review,
                    ]);
                }

                if (!is_array($settings->editorNotificationsOptions)) {
                    $settings->editorNotificationsOptions = [];
                }

                if ($review === null) {
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


    // Private Methods
    // =========================================================================

    private function _setSubmissionFromPost(): Submission
    {
        $request = Craft::$app->getRequest();
        $submissionId = $request->getParam('submissionId');

        if ($submissionId) {
            $submission = $this->getSubmissionById($submissionId);

            if (!$submission) {
                throw new \Exception(Craft::t('workflow', 'No submission with the ID “{id}”', ['id' => $submissionId]));
            }
        } else {
            $submission = new Submission();
        }

        return $submission;
    }

    private function _getRevisionData(Entry $revision): array
    {
        $revisionData = [
            'typeId' => $revision->typeId,
            'authorId' => $revision->authorId,
            'title' => $revision->title,
            'slug' => $revision->slug,
            'postDate' => $revision->postDate ? $revision->postDate->getTimestamp() : null,
            'expiryDate' => $revision->expiryDate ? $revision->expiryDate->getTimestamp() : null,
            'enabled' => $revision->enabled,
            'newParentId' => $revision->newParentId,
            'fields' => [],
        ];

        $content = $revision->getSerializedFieldValues();

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if (isset($content[$field->handle]) && $content[$field->handle] !== null) {
                $revisionData['fields'][$field->id] = $content[$field->handle];
            }
        }

        return $revisionData;
    }
}
