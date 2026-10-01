<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\DefinePublisherSelfApprovalEvent;
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

use DateTime;
use Throwable;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_GET_REVIEWER_USER_GROUPS = 'afterGetReviewerUserGroups';
    public const EVENT_DEFINE_PUBLISHER_SELF_APPROVAL = 'definePublisherSelfApproval';


    // Properties
    // =========================================================================

    public ?Submission $submission = null;


    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id, ?int $siteId = null): ?Submission
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, Submission::class, $siteId);
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
            $this->trigger(
                self::EVENT_AFTER_GET_REVIEWER_USER_GROUPS,
                new ReviewerUserGroupsEvent([
                    'submission' => $submission,
                    'userGroups' => $userGroups,
                ])
            );
        }

        return $userGroups;
    }

    public function canUserApproveOwnSubmission(User $user, Submission $submission): bool
    {
        if ($submission->getEditorId() !== $user->id) {
            return true;
        }

        $site = Craft::$app->getSites()->getSiteById($submission->ownerSiteId)
            ?? Craft::$app->getSites()->getPrimarySite();

        $settings = Workflow::$plugin->getSettings();

        $allow = false;

        if ($user->can('workflow-approve-own-submissions')) {
            $allow = true;
        }

        if ($settings->userMatchesPublisherSelfApprovalBypassGroups($user, $site)) {
            $allow = true;
        }

        $event = new DefinePublisherSelfApprovalEvent([
            'user' => $user,
            'submission' => $submission,
            'site' => $site,
            'allowSelfApproval' => $allow,
        ]);
        $this->trigger(self::EVENT_DEFINE_PUBLISHER_SELF_APPROVAL, $event);

        return $event->allowSelfApproval;
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

    public function saveSubmission(ElementInterface $entry, ?Submission $submission = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);
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

        // Refresh reviews cache to get the updated copies for emails
        $submission->clearReviews();

        // Trigger notification to reviewer
        if ($settings->reviewerNotifications) {
            Workflow::$plugin->getEmails()->sendReviewerNotificationEmail($submission, $review, $entry);
        } elseif ($settings->publisherNotifications) {
            Workflow::$plugin->getEmails()->sendPublisherNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Entry submitted for approval.'));

        return true;
    }

    public function revokeSubmission(ElementInterface $entry, ?Submission $submission = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Revoking a submission will set it as complete
        $submission = $this->_getSubmission($submission);
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

    public function approveReview(ElementInterface $entry, ?Submission $submission = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);

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

    public function rejectReview(ElementInterface $entry, ?Submission $submission = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Rejecting a submission will reset the pending state
        $submission = $this->_getSubmission($submission);
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

    public function approveSubmission(ElementInterface $entry, bool $published = true, ?Submission $submission = null)
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Approving the submission will complete the process
        $submission = $this->_getSubmission($submission);
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

        if ($settings->publishedAuthorNotifications && $published) {
            Workflow::$plugin->getEmails()->sendPublishedAuthorNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Entry approved and published.'));

        return true;
    }

    public function rejectSubmission(ElementInterface $entry, ?Submission $submission = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        // Rejecting a submission will reset the pending state
        $submission = $this->_getSubmission($submission);
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
        $entry = $submission->getDraft();

        if (!$entry) {
            return false;
        }

        if ($status === Review::STATUS_APPROVED) {
            // Assume we want to approve and publish
            $result = $this->approveSubmission($entry, true, $submission);

            if ($result && $entry->getIsDraft()) {
                Craft::$app->getDrafts()->applyDraft($entry);
            }

            return $result;
        } elseif ($status === Review::STATUS_REJECTED) {
            return $this->rejectSubmission($entry, $submission);
        } elseif ($status === Review::STATUS_REVOKED) {
            return $this->revokeSubmission($entry, $submission);
        }

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _getSubmission(?Submission $submission = null): Submission
    {
        if ($submission !== null) {
            return $submission;
        }

        // Preserve the existing programmatic context override as a one-shot fallback.
        if ($this->submission !== null) {
            $submission = $this->submission;
            $this->submission = null;

            return $submission;
        }

        return new Submission();
    }

    private function _setReviewFromPost(Submission $submission, ElementInterface $entry): Review
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

        $review = new Review();
        $review->submissionId = $submission->id;
        $review->elementId = $entry->getCanonicalId();
        $review->elementSiteId = $entry->siteId;
        $review->draftId = $entry->draftId;
        $review->userId = $currentUser->id;
        $review->setNotes((string)$request->getParam('workflowNotes'));
        $review->data = Workflow::$plugin->getContent()->getRevisionData($entry);

        return $review;
    }
}
