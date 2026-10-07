<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\actions\ApproveApplySubmission;
use verbb\workflow\actions\ApproveSubmission;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Component;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Db;
use craft\models\Site;

class SubmissionPermissions extends Component
{
    // Public Methods
    // =========================================================================

    public function canSubmit(User $user, Entry $entry, ?Submission $submission = null): bool
    {
        if ($entry->getIsRevision() || !$this->isSectionEnabled($entry) || !$this->_canSaveEntry($user, $entry)) {
            return false;
        }

        if (!$this->_isEditor($user, $entry->site)) {
            return false;
        }

        if ($submission === null) {
            if (!$entry->getCanonicalId()) {
                return true;
            }

            $existingSubmission = Submission::find()
                ->ownerId($entry->getCanonicalId())
                ->ownerSiteId($entry->siteId)
                ->ownerDraftId($entry->draftId ?? ':empty:')
                ->isComplete(false)
                ->limit(1)
                ->one();

            return $existingSubmission === null;
        }

        return $this->isSubmissionForEntry($submission, $entry)
            && !$submission->isComplete
            && !$submission->isPending
            && ($user->admin || $submission->editorId === $user->id);
    }

    public function canPerformAction(User $user, Entry $entry, Submission $submission, string $actionId): bool
    {
        if (!$this->_canActOnPendingSubmission($user, $entry, $submission)) {
            return false;
        }

        $role = Workflow::$plugin->getActions()->getActionRoleById($actionId);

        if ($role === Review::ROLE_EDITOR) {
            return $this->_isEditor($user, $entry->site)
                && ($user->admin || $submission->editorId === $user->id);
        }

        if ($role === Review::ROLE_REVIEWER) {
            return $user->admin || $this->isNextReviewer($user, $submission, $entry->site);
        }

        if ($role !== Review::ROLE_PUBLISHER || !$this->_isPublisher($user, $entry->site)) {
            return false;
        }

        if ($this->_isApprovalAction($actionId)) {
            if (!Workflow::$plugin->getSubmissions()->canUserApproveOwnSubmission($user, $submission)) {
                return false;
            }

            if ($this->_appliesDraft($actionId) && !$this->_canSaveCanonical($user, $entry)) {
                return false;
            }
        }

        return true;
    }

    public function canChangeStatus(User $user, Submission $submission, string $status): bool
    {
        $entry = $submission->getReviewableEntry();

        if (!$entry || !$this->_canActOnPendingSubmission($user, $entry, $submission)) {
            return false;
        }

        if ($status === Review::STATUS_APPROVED) {
            return $this->_isPublisher($user, $entry->site)
                && Workflow::$plugin->getSubmissions()->canUserApproveOwnSubmission($user, $submission)
                && $this->_canSaveCanonical($user, $entry);
        }

        if ($status === Review::STATUS_REJECTED) {
            return $this->_isPublisher($user, $entry->site);
        }

        if ($status === Review::STATUS_REVOKED) {
            return $this->_isPublisher($user, $entry->site)
                || ($this->_isEditor($user, $entry->site) && $submission->editorId === $user->id);
        }

        return false;
    }

    public function canEditDraft(User $user, Entry $entry, Submission $submission): bool
    {
        if (!$this->_canActOnPendingSubmission($user, $entry, $submission)) {
            return false;
        }

        return $user->admin
            || $this->_isPublisher($user, $entry->site)
            || $this->isNextReviewer($user, $submission, $entry->site);
    }

    public function canManageSubmission(User $user, Submission $submission): bool
    {
        if (!$user->can('workflow-overview') || !$user->can('accessPlugin-workflow')) {
            return false;
        }

        return $this->_isPublisher($user, $submission->getOwnerSite());
    }

    public function canDeleteSubmission(User $user, Submission $submission): bool
    {
        return $this->canManageSubmission($user, $submission);
    }

    public function canDeleteReview(User $user, Review $review): bool
    {
        $submission = $review->getSubmission();

        return $submission !== null && $this->canManageSubmission($user, $submission);
    }

    public function canViewReview(User $user, Review $review): bool
    {
        $submission = $review->getSubmission();

        if ($submission === null || !Craft::$app->getElements()->canView($submission, $user)) {
            return false;
        }

        $entry = $review->getElement() ?? $submission->getOwner();

        return $entry !== null && Craft::$app->getElements()->canView($entry, $user);
    }

    public function canViewReviewChanges(User $user, Review $review): bool
    {
        $submission = $review->getSubmission();
        $entry = $review->getElement() ?? $submission?->getOwner();

        if ($submission === null || !$entry instanceof Entry || !Craft::$app->getElements()->canView($entry, $user)) {
            return false;
        }

        if (Craft::$app->getElements()->canView($submission, $user)) {
            return true;
        }

        if (!$this->isSectionEnabled($entry)) {
            return false;
        }

        $settings = Workflow::$plugin->getSettings();
        $editorGroup = $settings->getEditorUserGroup($entry->site);
        $publisherGroup = $settings->getPublisherUserGroup($entry->site);

        if (($editorGroup && $user->isInGroup($editorGroup)) || ($publisherGroup && $user->isInGroup($publisherGroup))) {
            return true;
        }

        foreach (Workflow::$plugin->getSubmissions()->getReviewerUserGroups($entry->site, $submission) as $reviewerGroup) {
            if ($user->isInGroup($reviewerGroup)) {
                return true;
            }
        }

        return false;
    }

    public function isNextReviewer(User $user, Submission $submission, Site $site): bool
    {
        if ($user->admin) {
            return true;
        }

        $entry = $submission->getDraft() ?? $submission->getOwner();

        if (!$entry) {
            return false;
        }

        $nextReviewerGroup = Workflow::$plugin->getSubmissions()->getNextReviewerUserGroup($submission, $entry);

        return $nextReviewerGroup !== null && $user->isInGroup($nextReviewerGroup);
    }

    public function isSectionEnabled(Entry $entry): bool
    {
        $enabledSections = Workflow::$plugin->getSettings()->enabledSections;

        if (!$enabledSections) {
            return false;
        }

        if ($enabledSections === '*') {
            return true;
        }

        return in_array($entry->sectionId, Db::idsByUids(Table::SECTIONS, $enabledSections), true);
    }

    public function isSubmissionForEntry(Submission $submission, Entry $entry): bool
    {
        if (
            $submission->ownerId !== $entry->getCanonicalId() ||
            $submission->ownerSiteId !== $entry->siteId ||
            $submission->siteId !== $entry->siteId
        ) {
            return false;
        }

        $target = $submission->getReviewableEntry();

        return $target !== null
            && !$entry->getIsRevision()
            && $target->id === $entry->id
            && $target->draftId === $entry->draftId;
    }

    public function getAllowedStatuses(User $user, Submission $submission): array
    {
        $statuses = [];

        foreach (Review::statuses() as $status => $label) {
            if ($this->canChangeStatus($user, $submission, $status)) {
                $statuses[$status] = $label;
            }
        }

        return $statuses;
    }

    public function getAllowedBulkStatuses(User $user, Site $site): array
    {
        $statuses = Review::statuses();
        unset($statuses[Review::STATUS_PENDING]);

        if ($this->_isPublisher($user, $site)) {
            return $statuses;
        }

        $editorGroup = Workflow::$plugin->getSettings()->getEditorUserGroup($site);

        if ($editorGroup && $user->isInGroup($editorGroup)) {
            return [
                Review::STATUS_REVOKED => $statuses[Review::STATUS_REVOKED],
            ];
        }

        return [];
    }


    // Private Methods
    // =========================================================================

    private function _canActOnPendingSubmission(User $user, Entry $entry, Submission $submission): bool
    {
        return !$submission->isComplete
            && $submission->isPending
            && $this->isSubmissionForEntry($submission, $entry)
            && $this->_canSaveEntry($user, $entry);
    }

    private function _canSaveEntry(User $user, Entry $entry): bool
    {
        return Craft::$app->getElements()->canSave($entry, $user);
    }

    private function _canSaveCanonical(User $user, Entry $entry): bool
    {
        $elements = Craft::$app->getElements();

        if (method_exists($elements, 'canSaveCanonical')) {
            return $elements->canSaveCanonical($entry, $user);
        }

        // Craft added canSaveCanonical() during Craft 5. This mirrors its behavior for earlier Craft 5 releases.
        if ($entry->getIsUnpublishedDraft()) {
            $canonical = clone $entry;
            $canonical->draftId = null;
            $canonical->isProvisionalDraft = false;
        } else {
            $canonical = $entry->getCanonical(true);
        }

        return $canonical instanceof Entry && $elements->canSave($canonical, $user);
    }

    private function _isPublisher(User $user, Site $site): bool
    {
        if ($user->admin) {
            return true;
        }

        $publisherGroup = Workflow::$plugin->getSettings()->getPublisherUserGroup($site);

        return $publisherGroup !== null && $user->isInGroup($publisherGroup);
    }

    private function _isEditor(User $user, Site $site): bool
    {
        if ($user->admin) {
            return true;
        }

        $editorGroup = Workflow::$plugin->getSettings()->getEditorUserGroup($site);

        return $editorGroup !== null && $user->isInGroup($editorGroup);
    }

    private function _isApprovalAction(string $actionId): bool
    {
        return str_starts_with($actionId, 'approve-');
    }

    private function _appliesDraft(string $actionId): bool
    {
        return in_array($actionId, [
            ApproveApplySubmission::id(),
            ApproveSubmission::id(),
        ], true);
    }
}
