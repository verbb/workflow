<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\ReviewerUserGroupsEvent;
use verbb\workflow\models\Review;
use verbb\workflow\records\Review as ReviewRecord;

use Craft;
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

class Submissions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_GET_REVIEWER_USER_GROUPS = 'afterGetReviewerUserGroups';


    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id): ?Submission
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, Submission::class);
    }

    /**
     * Returns the reviewer user groups for the given submission.
     *
     * @param Submission|null $submission
     * @return UserGroup[]
     */
    public function getReviewerUserGroups($site, Submission $submission = null): array
    {
        $userGroups = Workflow::$plugin->getSettings()->getReviewerUserGroups($site);

        // Fire an 'afterGetReviewerUserGroups' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_GET_REVIEWER_USER_GROUPS)) {
            $this->trigger(self::EVENT_AFTER_GET_REVIEWER_USER_GROUPS,
                new ReviewerUserGroupsEvent([
                    'submission' => $submission,
                    'userGroups' => $userGroups,
                ])
            );
        }

        return $userGroups;
    }

    /**
     * Returns the next reviewer user group for the given submission.
     */
    public function getNextReviewerUserGroup(Submission $submission, $entry): ?UserGroup
    {
        $reviewerUserGroups = $this->getReviewerUserGroups($entry->site, $submission);

        $lastReviewer = $submission->getLastReviewer(true);

        if ($lastReviewer === null) {
            return $reviewerUserGroups[0] ?? null;
        }

        $nextUserGroup = null;

        foreach ($reviewerUserGroups as $key => $userGroup) {
            if ($lastReviewer->isInGroup($userGroup)) {
                $nextUserGroup = $reviewerUserGroups[$key + 1] ?? $nextUserGroup;
            }
        }

        return $nextUserGroup;
    }

    public function saveSubmission($entry = null): bool
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->setOwner($entry);
        $submission->editorId = $currentUser->id;
        $submission->status = Submission::STATUS_PENDING;
        $submission->dateApproved = null;
        $submission->editorNotes = StringHelper::htmlEncode((string)$request->getParam('editorNotes', $submission->editorNotes));
        $submission->publisherNotes = StringHelper::htmlEncode((string)$request->getParam('publisherNotes', $submission->publisherNotes));

        // If this is a draft, we need to keep track the ID of the canonical entry for later.
        if ($entry->getIsDraft()) {
            $submission->ownerDraftId = $entry->draftId;
            $submission->ownerCanonicalId = $entry->getCanonicalId();
        }

        $submission->data = $this->_getRevisionData($entry);

        $isNew = !$submission->id;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not submit for approval.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        if ($isNew) {
            // Trigger notification to reviewer
            if ($settings->reviewerNotifications) {
                Workflow::$plugin->getEmails()->sendReviewerNotificationEmail($submission, $entry);
            } else if ($settings->publisherNotifications) {
                Workflow::$plugin->getEmails()->sendPublisherNotificationEmail($submission, $entry);
            }
        }

        $session->setNotice(Craft::t('workflow', 'Entry submitted for approval.'));

        return true;
    }

    public function revokeSubmission($entry): bool
    {
        $settings = Workflow::$plugin->getSettings();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REVOKED;
        $submission->dateRevoked = new DateTime;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not revoke submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        $session->setNotice(Craft::t('workflow', 'Submission revoked.'));

        return true;
    }

    public function approveReview($entry): bool
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

            return false;
        }

        $reviewRecord = new ReviewRecord([
            'submissionId' => $submission->id,
            'userId' => $currentUser->id,
            'approved' => true,
            'notes' => $request->getParam('reviewerNotes'),
        ]);

        if (!$reviewRecord->save()) {
            $session->setError(Craft::t('workflow', 'Could not approve submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        $review = Review::populateModel($reviewRecord);

        // Trigger notification to the next reviewer, if there is one
        if ($settings->reviewerNotifications) {
            // Modify the notes to be the reviewer notes, but still use the same email template
            $submission->editorNotes = StringHelper::htmlEncode((string)$reviewRecord->notes);

            Workflow::$plugin->getEmails()->sendReviewerNotificationEmail($submission, $entry);
        }

        // Trigger notification to editor - if configured to do so
        if ($settings->editorNotifications && $settings->reviewerApprovalNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review);
        }

        $session->setNotice(Craft::t('workflow', 'Submission approved.'));

        return true;
    }

    public function rejectReview($entry): bool
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REJECTED;
        $submission->dateRejected = new DateTime;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        $reviewRecord = new ReviewRecord([
            'submissionId' => $submission->id,
            'userId' => $currentUser->id,
            'approved' => false,
            'notes' => $request->getParam('reviewerNotes'),
        ]);

        if (!$reviewRecord->save()) {
            $session->setError(Craft::t('workflow', 'Could not reject submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        $review = Review::populateModel($reviewRecord);

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));

        return true;
    }

    public function approveSubmission($entry, $published = true)
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_APPROVED;
        $submission->publisherId = $currentUser->id;
        $submission->dateApproved = new DateTime;
        $submission->editorNotes = StringHelper::htmlEncode((string)$request->getParam('editorNotes', $submission->editorNotes));
        $submission->publisherNotes = StringHelper::htmlEncode((string)$request->getParam('publisherNotes', $submission->publisherNotes));

        // If this was a draft, the `ownerId` and `ownerDraftId` will now be gone thanks to foreign key checks.
        // But, we want to switch back to the canonical, original entry
        if ($published && $submission->ownerCanonicalId) {
            if ($entry = Craft::$app->getElements()->getElementById($submission->ownerCanonicalId)) {
                $submission->setOwner($entry);
            }
        }

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve and publish.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('workflow', 'Entry approved and published.'));

        return true;
    }

    public function rejectSubmission($entry): bool
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REJECTED;
        $submission->publisherId = $currentUser->id;
        $submission->dateRejected = new DateTime;
        $submission->editorNotes = StringHelper::htmlEncode((string)$request->getParam('editorNotes', $submission->editorNotes));
        $submission->publisherNotes = StringHelper::htmlEncode((string)$request->getParam('publisherNotes', $submission->publisherNotes));

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not reject submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));

        return true;
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
                throw new Exception(Craft::t('workflow', 'No submission with the ID “{id}”', ['id' => $submissionId]));
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
            // 'newParentId' => $revision->newParentId,
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
