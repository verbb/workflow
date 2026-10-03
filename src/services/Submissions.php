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
use craft\models\UserGroup;

use Throwable;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_GET_REVIEWER_USER_GROUPS = 'afterGetReviewerUserGroups';
    public const EVENT_DEFINE_PUBLISHER_SELF_APPROVAL = 'definePublisherSelfApproval';
    public const TRANSITION_LOCK_TIMEOUT = 5;


    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?int $expectedReviewId = null;


    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id, ?int $siteId = null): ?Submission
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, Submission::class, $siteId);
    }

    public function getLatestReviewId(Submission $submission): ?int
    {
        return $this->_latestReview($submission)?->id;
    }

    public function getTransitionLockName(Submission $submission, ElementInterface $entry): string
    {
        $ownerId = $submission->ownerId ?? $entry->getCanonicalId();
        $siteId = $submission->ownerSiteId ?? $entry->siteId;
        $draftId = $entry->draftId ?? $this->_latestReview($submission)?->draftId ?? 0;

        return sprintf('workflow:transition:%d:%d:%d', $ownerId, $siteId, $draftId);
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

    public function saveSubmission(ElementInterface $entry, ?Submission $submission = null, ?int $expectedReviewId = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);
        $expectedReviewId = $this->_consumeExpectedReviewId($expectedReviewId);
        $review = null;

        if (!$this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry, &$review): bool {
            $review = $this->createReview($currentSubmission, $entry);
            $review->role = Review::ROLE_EDITOR;
            $review->status = Review::STATUS_PENDING;

            return $this->_saveTransition($currentSubmission, $review, [
                'siteId' => $entry->siteId,
                'ownerId' => $entry->getCanonicalId(),
                'ownerSiteId' => $entry->siteId,
                'isComplete' => false,
                'isPending' => true,
            ], 'Could not submit for approval.');
        })) {
            return false;
        }

        // Trigger notification to reviewer
        if ($settings->reviewerNotifications) {
            Workflow::$plugin->getEmails()->sendReviewerNotificationEmail($submission, $review, $entry);
        } elseif ($settings->publisherNotifications) {
            Workflow::$plugin->getEmails()->sendPublisherNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Entry submitted for approval.'));

        return true;
    }

    public function revokeSubmission(ElementInterface $entry, ?Submission $submission = null, ?int $expectedReviewId = null): bool
    {
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);
        $expectedReviewId = $this->_consumeExpectedReviewId($expectedReviewId);

        if (!$this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry): bool {
            $review = $this->createReview($currentSubmission, $entry);
            $review->role = Review::ROLE_EDITOR;
            $review->status = Review::STATUS_REVOKED;

            return $this->_saveTransition($currentSubmission, $review, [
                'isComplete' => true,
                'isPending' => false,
            ], 'Could not revoke submission.');
        })) {
            return false;
        }

        $session->setNotice(Craft::t('workflow', 'Submission revoked.'));

        return true;
    }

    public function approveReview(ElementInterface $entry, ?Submission $submission = null, ?int $expectedReviewId = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);
        $expectedReviewId = $this->_consumeExpectedReviewId($expectedReviewId);
        $review = null;

        if (!$this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry, &$review): bool {
            $review = $this->createReview($currentSubmission, $entry);
            $review->role = Review::ROLE_REVIEWER;
            $review->status = Review::STATUS_APPROVED;

            if (!Workflow::$plugin->getReviews()->saveReview($review)) {
                $this->_setReviewFailure($currentSubmission, $review);
                return false;
            }

            $currentSubmission->clearReviews();
            return true;
        })) {
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

    public function rejectReview(ElementInterface $entry, ?Submission $submission = null, ?int $expectedReviewId = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);
        $expectedReviewId = $this->_consumeExpectedReviewId($expectedReviewId);
        $review = null;

        if (!$this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry, &$review): bool {
            $review = $this->createReview($currentSubmission, $entry);
            $review->role = Review::ROLE_REVIEWER;
            $review->status = Review::STATUS_REJECTED;

            return $this->_saveTransition($currentSubmission, $review, [
                'isPending' => false,
            ], 'Could not revoke submission.');
        })) {
            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));

        return true;
    }

    public function approveSubmission(ElementInterface $entry, bool $published = true, ?Submission $submission = null, ?int $expectedReviewId = null)
    {
        $submission = $this->_getSubmission($submission);
        $expectedReviewId = $this->_consumeExpectedReviewId($expectedReviewId);
        $review = null;

        if (!$this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry, &$review): bool {
            $review = $this->createReview($currentSubmission, $entry);
            return $this->_approveSubmission($entry, $currentSubmission, $review);
        })) {
            return false;
        }

        $this->_sendApprovalNotifications($entry, $submission, $review, $published);

        return true;
    }

    public function approveSubmissionForApplication(ElementInterface $entry, Submission $submission, Review $review, ?int $expectedReviewId = null): bool
    {
        return $this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry, $review): bool {
            $review->elementId = $entry->getCanonicalId();
            $review->elementSiteId = $entry->siteId;
            $review->draftId = null;
            $review->data = Workflow::$plugin->getContent()->getRevisionData($entry);

            return $this->_approveSubmission($entry, $currentSubmission, $review);
        });
    }

    public function finalizeAppliedSubmission(ElementInterface $entry, Submission $submission, Review $review): void
    {
        $submission->clearReviews();
        $submission->clearDraft();

        $this->_sendApprovalNotifications($entry, $submission, $review, true);
    }

    public function createReview(Submission $submission, ElementInterface $entry): Review
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

    public function rejectSubmission(ElementInterface $entry, ?Submission $submission = null, ?int $expectedReviewId = null): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $session = Craft::$app->getSession();

        $submission = $this->_getSubmission($submission);
        $expectedReviewId = $this->_consumeExpectedReviewId($expectedReviewId);
        $review = null;

        if (!$this->_withTransitionLock($submission, $entry, $expectedReviewId, function(Submission $currentSubmission) use ($entry, &$review): bool {
            $review = $this->createReview($currentSubmission, $entry);
            $review->role = Review::ROLE_PUBLISHER;
            $review->status = Review::STATUS_REJECTED;

            return $this->_saveTransition($currentSubmission, $review, [
                'isPending' => false,
            ], 'Could not revoke submission.');
        })) {
            return false;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review, $entry);
        }

        $session->setNotice(Craft::t('workflow', 'Submission rejected.'));

        return true;
    }

    public function triggerSubmissionStatus(string $status, Submission $submission, ?int $expectedReviewId = null): bool
    {
        $entry = $submission->getDraft();

        if (!$entry) {
            return false;
        }

        if ($status === Review::STATUS_APPROVED) {
            if (!$entry->getIsDraft()) {
                return $this->approveSubmission($entry, true, $submission, $expectedReviewId);
            }

            Craft::$app->getDrafts()->applyDraft($entry);

            $savedSubmission = $this->getSubmissionById($submission->id, $submission->siteId);

            if (!$savedSubmission || !$savedSubmission->isComplete || $savedSubmission->isPending) {
                return false;
            }

            // Keep the caller's instance in sync with the copy loaded during draft application.
            $submission->isComplete = $savedSubmission->isComplete;
            $submission->isPending = $savedSubmission->isPending;
            $submission->clearReviews();
            $submission->clearDraft();

            return true;
        } elseif ($status === Review::STATUS_REJECTED) {
            return $this->rejectSubmission($entry, $submission, $expectedReviewId);
        } elseif ($status === Review::STATUS_REVOKED) {
            return $this->revokeSubmission($entry, $submission, $expectedReviewId);
        }

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _approveSubmission(ElementInterface $entry, Submission $submission, Review $review): bool
    {
        $review->role = Review::ROLE_PUBLISHER;
        $review->status = Review::STATUS_APPROVED;

        if (!$this->_saveTransition($submission, $review, [
            'isComplete' => true,
            'isPending' => false,
        ], 'Could not approve and publish.')) {
            return false;
        }

        return true;
    }

    private function _sendApprovalNotifications(ElementInterface $entry, Submission $submission, Review $review, bool $published): void
    {
        $settings = Workflow::$plugin->getSettings();

        if ($settings->editorNotifications) {
            Workflow::$plugin->getEmails()->sendEditorNotificationEmail($submission, $review, $entry);
        }

        if ($settings->publishedAuthorNotifications && $published) {
            Workflow::$plugin->getEmails()->sendPublishedAuthorNotificationEmail($submission, $review, $entry);
        }

        Craft::$app->getSession()->setNotice(Craft::t('workflow', 'Entry approved and published.'));
    }

    private function _withTransitionLock(Submission $submission, ElementInterface $entry, ?int $expectedReviewId, callable $callback): bool
    {
        $expectedState = [
            'isComplete' => (bool)$submission->isComplete,
            'isPending' => (bool)$submission->isPending,
            'reviewId' => $submission->id === null ? null : ($expectedReviewId ?? $this->getLatestReviewId($submission)),
        ];
        $lockName = $this->getTransitionLockName($submission, $entry);
        $mutex = Craft::$app->getMutex();
        $ownsLock = !$mutex->isAcquired($lockName);

        if ($ownsLock && !$mutex->acquire($lockName, self::TRANSITION_LOCK_TIMEOUT)) {
            $this->_setTransitionConflict($submission);
            return false;
        }

        try {
            $currentSubmission = $this->_currentTransitionSubmission($submission, $entry, $expectedState);

            if ($currentSubmission === null) {
                $this->_setTransitionConflict($submission);
                return false;
            }

            if (!$callback($currentSubmission)) {
                return false;
            }

            $this->_syncTransitionSubmission($submission, $currentSubmission);
            return true;
        } finally {
            if ($ownsLock) {
                $mutex->release($lockName);
            }
        }
    }

    private function _currentTransitionSubmission(Submission $submission, ElementInterface $entry, array $expectedState): ?Submission
    {
        if ($submission->id === null) {
            $existingSubmissions = Submission::find()
                ->ownerId($entry->getCanonicalId())
                ->ownerSiteId($entry->siteId)
                ->ownerDraftId($entry->draftId)
                ->siteId($entry->siteId)
                ->status(null)
                ->all();

            foreach ($existingSubmissions as $existingSubmission) {
                if (!$existingSubmission->isComplete) {
                    return null;
                }
            }

            return $submission;
        }

        $currentSubmission = Submission::find()
            ->id($submission->id)
            ->siteId($entry->siteId)
            ->status(null)
            ->one();

        if ($currentSubmission === null) {
            return null;
        }

        $currentState = [
            'isComplete' => (bool)$currentSubmission->isComplete,
            'isPending' => (bool)$currentSubmission->isPending,
            'reviewId' => $this->getLatestReviewId($currentSubmission),
        ];

        return $currentState === $expectedState ? $currentSubmission : null;
    }

    private function _latestReview(Submission $submission): ?Review
    {
        $latestReview = null;

        foreach ($submission->getReviews() as $review) {
            if ($review->id !== null && ($latestReview === null || $review->id > $latestReview->id)) {
                $latestReview = $review;
            }
        }

        return $latestReview;
    }

    private function _consumeExpectedReviewId(?int $expectedReviewId): ?int
    {
        $expectedReviewId ??= $this->expectedReviewId;
        $this->expectedReviewId = null;

        return $expectedReviewId;
    }

    private function _syncTransitionSubmission(Submission $submission, Submission $currentSubmission): void
    {
        if ($submission === $currentSubmission) {
            return;
        }

        $submission->id = $currentSubmission->id;
        $submission->uid = $currentSubmission->uid;
        $submission->isComplete = $currentSubmission->isComplete;
        $submission->isPending = $currentSubmission->isPending;
        $submission->clearReviews();
        $submission->clearDraft();
    }

    private function _saveTransition(Submission $submission, Review $review, array $attributes, string $submissionError): bool
    {
        if (!$review->validate()) {
            $this->_setReviewFailure($submission, $review);
            return false;
        }

        $originalAttributes = [];

        foreach ($attributes as $attribute => $value) {
            $originalAttributes[$attribute] = $submission->$attribute;
            $submission->$attribute = $value;
        }

        $originalSubmissionId = $submission->id;
        $originalSubmissionUid = $submission->uid;
        $originalReviewId = $review->id;
        $originalReviewSubmissionId = $review->submissionId;
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if (!Craft::$app->getElements()->saveElement($submission)) {
                $transaction->rollBack();
                $this->_restoreTransition($submission, $review, $originalAttributes, $originalSubmissionId, $originalSubmissionUid, $originalReviewId, $originalReviewSubmissionId);
                $this->_setSubmissionFailure($submission, $submissionError);

                return false;
            }

            $review->submissionId = $submission->id;

            if (!Workflow::$plugin->getReviews()->saveReview($review)) {
                $transaction->rollBack();
                $this->_restoreTransition($submission, $review, $originalAttributes, $originalSubmissionId, $originalSubmissionUid, $originalReviewId, $originalReviewSubmissionId);
                $this->_setReviewFailure($submission, $review);

                return false;
            }

            $transaction->commit();
        } catch (Throwable $e) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            $this->_restoreTransition($submission, $review, $originalAttributes, $originalSubmissionId, $originalSubmissionUid, $originalReviewId, $originalReviewSubmissionId);
            throw $e;
        }

        $submission->clearReviews();

        return true;
    }

    private function _restoreTransition(Submission $submission, Review $review, array $attributes, ?int $submissionId, ?string $submissionUid, ?int $reviewId, ?int $reviewSubmissionId): void
    {
        foreach ($attributes as $attribute => $value) {
            $submission->$attribute = $value;
        }

        $submission->id = $submissionId;
        $submission->uid = $submissionUid;
        $submission->clearReviews();
        $review->id = $reviewId;
        $review->submissionId = $reviewSubmissionId;
    }

    private function _setSubmissionFailure(Submission $submission, string $message): void
    {
        Craft::$app->getSession()->setError(Craft::t('workflow', $message));

        Craft::$app->getUrlManager()->setRouteParams([
            'submission' => $submission,
        ]);
    }

    private function _setReviewFailure(Submission $submission, Review $review): void
    {
        Craft::$app->getSession()->setError(Craft::t('workflow', 'Could not save review.'));

        Craft::$app->getUrlManager()->setRouteParams([
            'submission' => $submission,
            'review' => $review,
        ]);
    }

    private function _setTransitionConflict(Submission $submission): void
    {
        Craft::$app->getSession()->setError(Craft::t('workflow', 'This submission changed before the action could be completed. Please reload and try again.'));

        Craft::$app->getUrlManager()->setRouteParams([
            'submission' => $submission,
        ]);
    }

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

}
