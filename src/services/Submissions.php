<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\ReviewerUserGroupsEvent;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
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


    // Properties
    // =========================================================================

    public ?Submission $submission = null;


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

        $lastReviewer = $submission->getReviewer();

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

    public function saveSubmission(ElementInterface $entry): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->siteId = $entry->siteId;
        $submission->ownerId = $entry->getCanonicalId();
        $submission->ownerSiteId = $entry->siteId;
        $submission->isComplete = false;
        $submission->isPending = true;

        $isNew = !$submission->id;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not submit for approval.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        // Create a new review
        $review = $this->_setReviewFromPost($submission, $entry);
        $review->role = Review::ROLE_EDITOR;
        $review->status = Review::STATUS_PENDING;

        if (!Workflow::$plugin->getReviews()->saveReview($review)) {
            $session->setError(Craft::t('workflow', 'Could not save review.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'review' => $review,
            ]);

            return false;
        }

        // Trigger notification to reviewer
        if ($settings->reviewerNotifications) {
            Workflow::$plugin->getEmails()->sendReviewerNotificationEmail($submission, $review, $entry);
        } else if ($settings->publisherNotifications) {
            Workflow::$plugin->getEmails()->sendPublisherNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Entry submitted for approval.'));

        return true;
    }

    public function revokeSubmission(ElementInterface $entry): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Revoking a submission will set it as complete
        $submission = $this->_setSubmissionFromPost();
        $submission->isComplete = true;
        $submission->isPending = false;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not revoke submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        // Create a new review
        $review = $this->_setReviewFromPost($submission, $entry);
        $review->role = Review::ROLE_EDITOR;
        $review->status = Review::STATUS_REVOKED;

        if (!Workflow::$plugin->getReviews()->saveReview($review)) {
            $session->setError(Craft::t('workflow', 'Could not save review.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'review' => $review,
            ]);

            return false;
        }

        $session->setNotice(Craft::t('workflow', 'Submission revoked.'));

        return true;
    }

    public function approveReview(ElementInterface $entry): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();

        // Create a new review
        $review = $this->_setReviewFromPost($submission, $entry);
        $review->role = Review::ROLE_REVIEWER;
        $review->status = Review::STATUS_APPROVED;

        if (!Workflow::$plugin->getReviews()->saveReview($review)) {
            $session->setError(Craft::t('workflow', 'Could not save review.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'review' => $review,
            ]);

            return false;
        }

        // Trigger notification to the next reviewer, if there is one
        if ($settings->reviewerNotifications) {
            Workflow::$plugin->getEmails()->sendReviewerNotificationEmail($submission, $review, $entry);
        }

        // Trigger notification to editor - if configured to do so
        if ($settings->editorNotifications && $settings->reviewerApprovalNotifications) {
            Workflow::$plugin->getEmails()->sendEditorReviewNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Submission approved.'));

        return true;
    }

    public function rejectReview(ElementInterface $entry): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Rejecting a submission will reset the pending state
        $submission = $this->_setSubmissionFromPost();
        $submission->isPending = false;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not revoke submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        // Create a new review
        $review = $this->_setReviewFromPost($submission, $entry);
        $review->role = Review::ROLE_REVIEWER;
        $review->status = Review::STATUS_REJECTED;

        if (!Workflow::$plugin->getReviews()->saveReview($review)) {
            $session->setError(Craft::t('workflow', 'Could not save review.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'review' => $review,
            ]);

            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));

        return true;
    }

    public function approveSubmission(ElementInterface $entry, bool $published = true)
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Approving the submission will complete the process
        $submission = $this->_setSubmissionFromPost();
        $submission->isComplete = true;
        $submission->isPending = false;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve and publish.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }

        // Create a new review
        $review = $this->_setReviewFromPost($submission, $entry);
        $review->role = Review::ROLE_PUBLISHER;
        $review->status = Review::STATUS_APPROVED;

        if (!Workflow::$plugin->getReviews()->saveReview($review)) {
            $session->setError(Craft::t('workflow', 'Could not save review.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'review' => $review,
            ]);

            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Entry approved and published.'));

        return true;
    }

    public function rejectSubmission(ElementInterface $entry): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Rejecting a submission will reset the pending state
        $submission = $this->_setSubmissionFromPost();
        $submission->isPending = false;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not revoke submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return false;
        }
        
        // Create a new review
        $review = $this->_setReviewFromPost($submission, $entry);
        $review->role = Review::ROLE_PUBLISHER;
        $review->status = Review::STATUS_REJECTED;

        if (!Workflow::$plugin->getReviews()->saveReview($review)) {
            $session->setError(Craft::t('workflow', 'Could not save review.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'review' => $review,
            ]);

            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));

        return true;
    }

    public function triggerSubmissionStatus(string $status, Submission $submission): bool
    {
        // Set the submission for context
        $this->submission = $submission;
        $entry = $submission->getOwner();

        if ($status === Review::STATUS_APPROVED) {
            // Assume we want to approve and publish
            $result = $this->approveSubmission($entry, true);

            if ($lastReview = $submission->getLastReview()) {
                if ($element = $lastReview->getElement()) {
                    if ($element->getIsDraft()) {
                        Craft::$app->getDrafts()->applyDraft($element);
                    }
                }
            }

            return $result;
        } else if ($status === Review::STATUS_REJECTED) {
            return $this->rejectSubmission($entry);
        } else if ($status === Review::STATUS_REVOKED) {
            return $this->revokeSubmission($entry);
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _setSubmissionFromPost(): Submission
    {
        // Allow the submission to be set on this class
        if ($this->submission) {
            return $this->submission;
        }

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

    private function _setReviewFromPost(Submission $submission, ElementInterface $entry): Review
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

        $review = new Review();
        $review->submissionId = $submission->id;
        $review->elementId = $entry->id;
        $review->draftId = $entry->draftId;
        $review->userId = $currentUser->id;
        $review->setNotes((string)$request->getParam('workflowNotes'));
        $review->data = Workflow::$plugin->getContent()->getRevisionData($entry);

        return $review;
    }
}
