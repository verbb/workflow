<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\helpers\StringHelper;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\events\CreateFieldLayoutFormEvent;
use craft\events\DefineHtmlEvent;
use craft\events\DraftEvent;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\helpers\ArrayHelper;

use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;

use Throwable;

class Service extends Component
{
    // Properties
    // =========================================================================

    public bool $afterSaveRun = false;

    /** @var array{submission: Submission, review: Review, ownerId: int, siteId: int, draftElementId: int, expectedReviewId: int|null, lockName: string, approved: bool}|null */
    private ?array $_draftApproval = null;
    private ?string $_entryActionLockName = null;
    private ?int $_expectedReviewId = null;


    // Public Methods
    // =========================================================================

    public function onBeforeSaveEntry(ModelEvent $event): void
    {
        $settings = Workflow::$plugin->getSettings();
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');
        $currentUser = Craft::$app->getUser()->getIdentity();

        // Don't trigger for propagating elements
        if ($event->sender->propagating) {
            return;
        }

        // Don't trigger for Matrix/etc which are entries
        if ($event->sender->fieldId) {
            return;
        }

        $this->_expectedReviewId = null;

        $currentSite = Craft::$app->getSites()->getCurrentSite();

        // Sanitize notes first
        $workflowNotes = StringHelper::sanitizeNotes((string)$request->getBodyParam('workflowNotes'));

        // Save the notes for later, due to a number of different events triggering
        Craft::$app->getUrlManager()->setRouteParams([
            'workflowNotes' => StringHelper::unSanitizeNotes($workflowNotes),
        ]);

        $submissionId = (int)$request->getBodyParam('submissionId');
        $submission = $submissionId ? Workflow::$plugin->getSubmissions()->getSubmissionById($submissionId, $event->sender->siteId) : null;
        $this->_expectedReviewId = $this->_getExpectedReviewId($submission);
        $customAction = $action ? Workflow::$plugin->getActions()->getActionById($action) : null;
        $actionAllowed = false;

        if ($action) {
            if (!$currentUser || ($submissionId && !$submission)) {
                $this->_denyEntrySave($event, Craft::t('workflow', 'Unable to perform this Workflow action.'));
                return;
            }

            $submissionsService = Workflow::$plugin->getSubmissions();
            $lockSubmission = $submission ?? new Submission();
            $lockName = $submissionsService->getTransitionLockName($lockSubmission, $event->sender);
            $appliesDraft = in_array($action, ['approve-submission', 'approve-apply-submission'], true);

            if (!$appliesDraft && !$this->_acquireEntryActionLock($lockName)) {
                $this->_denyEntrySave($event, Craft::t('workflow', 'This submission is already being updated. Please reload and try again.'));
                return;
            }

            if ($submissionId) {
                $submission = Submission::find()
                    ->id($submissionId)
                    ->siteId($event->sender->siteId)
                    ->status(null)
                    ->one();

                if (!$submission) {
                    $this->_denyEntrySave($event, Craft::t('workflow', 'Unable to perform this Workflow action.'));
                    return;
                }
            } elseif ($action === 'save-submission') {
                $activeSubmission = Submission::find()
                    ->ownerId($event->sender->getCanonicalId())
                    ->ownerSiteId($event->sender->siteId)
                    ->ownerDraftId($event->sender->draftId)
                    ->siteId($event->sender->siteId)
                    ->status(null)
                    ->isComplete(false)
                    ->one();

                if ($activeSubmission) {
                    $this->_denyEntrySave($event, Craft::t('workflow', 'This submission changed before the action could be completed. Please reload and try again.'));
                    return;
                }
            }

            if ($submission && Workflow::$plugin->getSubmissions()->getLatestReviewId($submission) !== $this->_expectedReviewId) {
                $this->_denyEntrySave($event, Craft::t('workflow', 'This submission changed before the action could be completed. Please reload and try again.'));
                return;
            }

            if ($action === 'save-submission') {
                $actionAllowed = Workflow::$plugin->getSubmissionPermissions()->canSubmit($currentUser, $event->sender, $submission);
            } elseif ($customAction && $submission) {
                $actionAllowed = Workflow::$plugin->getSubmissionPermissions()->canPerformAction($currentUser, $event->sender, $submission, $action);
            }

            if (!$actionAllowed) {
                $this->_denyEntrySave($event, Craft::t('workflow', 'You are not allowed to perform this Workflow action.'));
                return;
            }
        }

        // Disable auto-save for an entry that has been submitted. Only real way to do this.
        // A request action only bypasses the lock after the same server-side authorization used for the action itself.
        if (!$actionAllowed && $event->sender->getIsDraft()) {
            // Check to see if there's a matching pending (submitted) Workflow submission
            $pendingSubmission = Submission::find()
                ->ownerId($event->sender->getCanonicalId())
                ->ownerSiteId($event->sender->siteId)
                ->ownerDraftId($event->sender->draftId)
                ->limit(1)
                ->isComplete(false)
                ->isPending(true)
                ->one();

            if (
                $pendingSubmission !== null &&
                $settings->lockDraftSubmissions &&
                (!$currentUser || !Workflow::$plugin->getSubmissionPermissions()->canEditDraft($currentUser, $event->sender, $pendingSubmission))
            ) {
                $this->_denyEntrySave($event, Craft::t('workflow', 'Unable to edit entry once it has been submitted for review.'));
            }
        }

        if ($action === 'save-submission') {
            // If this is a front-end request, and if this is a draft, don't trigger live mode yet.
            // This is because we need to create the draft first, then save that
            if (Craft::$app->getRequest()->getIsSiteRequest() && $event->sender->getIsDraft() && !$event->sender->id) {
                return;
            }

            // Content validation won't trigger unless its set to 'live' - but that won't happen because an editor
            // can't publish. We quickly switch it on to make sure the entry validates correctly.
            $event->sender->setScenario(Element::SCENARIO_LIVE);
            $event->sender->validate();

            // We also need to validate notes fields, if required before we save the entry
            if ($settings->getEditorNotesRequired($currentSite) && !$workflowNotes) {
                Craft::$app->getUrlManager()->setRouteParams([
                    'workflowNotesErrors' => [Craft::t('workflow', 'Notes are required')],
                ]);

                $event->isValid = false;
            }
        }

        // Check for any actions registered for this actionId
        if ($actionAllowed && $customAction) {
            $submissionsService = Workflow::$plugin->getSubmissions();
            $submissionsService->submission = $submission;
            $submissionsService->expectedReviewId = $this->_expectedReviewId;

            try {
                $customAction->onBeforeSaveEntry($event);
            } finally {
                $submissionsService->submission = null;
                $submissionsService->expectedReviewId = null;
            }
        }

        if (!$event->isValid) {
            $this->_releaseEntryActionLock();
        }
    }

    public function onAfterSaveElement(ElementEvent $event): void
    {
        if (!($event->element instanceof Entry)) {
            return;
        }

        // Don't trigger for Matrix/etc which are entries
        if ($event->element->fieldId) {
            return;
        }

        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');
        $currentUser = Craft::$app->getUser()->getIdentity();

        // When approving, we don't want to perform an action here - wait until the draft has been applied
        if (!$action || $event->element->propagating || $this->afterSaveRun) {
            return;
        }

        // This helps us maintain whether the after-save event has already been triggered for this
        // request, and not to have it run again. This is most commonly caused by Preparse fields
        // which re-save the element again, straight after it's first save. Then we end up with multiple
        // submissions, created each time it's called.
        $this->afterSaveRun = true;

        $submissionId = (int)$request->getBodyParam('submissionId');
        $submission = $submissionId ? Workflow::$plugin->getSubmissions()->getSubmissionById($submissionId, $event->element->siteId) : null;

        // Check if we're submitting a new submission
        if ($action == 'save-submission') {
            // If this is a front-end request, and if this is a draft, don't trigger live mode yet.
            // This is because we need to create the draft first, then save that
            if (Craft::$app->getRequest()->getIsSiteRequest() && $event->element->scenario === Element::SCENARIO_ESSENTIALS) {
                // This is when the draft entry is saved, we want to process this again next time
                $this->afterSaveRun = false;

                return;
            }

            if (!$currentUser || !Workflow::$plugin->getSubmissionPermissions()->canSubmit($currentUser, $event->element, $submission)) {
                $this->_releaseEntryActionLock();
                return;
            }

            if (!Workflow::$plugin->getSubmissions()->saveSubmission($event->element, $submission, $this->_expectedReviewId)) {
                $this->_releaseEntryActionLock();
                throw new InvalidElementException($event->element, Craft::t('workflow', 'Could not submit for approval.'));
            }

            $this->_expectedReviewId = null;
        }

        // Check for any actions registered for this actionId
        if ($action && $customAction = Workflow::$plugin->getActions()->getActionById($action)) {
            if (
                $currentUser &&
                $submission &&
                Workflow::$plugin->getSubmissionPermissions()->canPerformAction($currentUser, $event->element, $submission, $action)
            ) {
                $submissionsService = Workflow::$plugin->getSubmissions();
                $submissionsService->submission = $submission;
                $submissionsService->expectedReviewId = $this->_expectedReviewId;

                try {
                    $customAction->onAfterSaveElement($event);
                } finally {
                    $submissionsService->submission = null;
                    $submissionsService->expectedReviewId = null;
                    $this->_expectedReviewId = null;
                    $this->_releaseEntryActionLock();
                }
            }
        }

        $this->_releaseEntryActionLock();
    }

    public function onBeforeApplyDraft(DraftEvent $event): void
    {
        $this->_draftApproval = null;

        if (!($event->draft instanceof Entry)) {
            return;
        }

        $submission = Submission::find()
            ->ownerId($event->draft->getCanonicalId())
            ->ownerSiteId($event->draft->siteId)
            ->ownerDraftId($event->draft->draftId)
            ->limit(1)
            ->isComplete(false)
            ->isPending(true)
            ->one();

        if (!$submission) {
            $request = Craft::$app->getRequest();

            if ($request->getBodyParam('workflow-action') !== null || $request->getBodyParam('workflowReviewId') !== null) {
                throw new ConflictHttpException('This Workflow submission changed before the draft could be applied.');
            }

            return;
        }

        $submissionsService = Workflow::$plugin->getSubmissions();
        $expectedReviewId = $this->_getExpectedReviewId($submission);
        $lockName = $submissionsService->getTransitionLockName($submission, $event->draft);
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire($lockName, Submissions::TRANSITION_LOCK_TIMEOUT)) {
            throw new ConflictHttpException('This Workflow submission is already being updated.');
        }

        try {
            // Re-read after serialization so a concurrent transition cannot apply the same stage twice.
            $submission = Submission::find()
                ->ownerId($event->draft->getCanonicalId())
                ->ownerSiteId($event->draft->siteId)
                ->ownerDraftId($event->draft->draftId)
                ->limit(1)
                ->isComplete(false)
                ->isPending(true)
                ->one();

            if (!$submission || $submissionsService->getLatestReviewId($submission) !== $expectedReviewId) {
                throw new ConflictHttpException('This Workflow submission changed before the draft could be applied.');
            }

            $currentUser = Craft::$app->getUser()->getIdentity();

            // Direct draft application must obey the same publisher, self-approval, target and Craft permission checks.
            if (!$currentUser || !Workflow::$plugin->getSubmissionPermissions()->canChangeStatus($currentUser, $submission, Review::STATUS_APPROVED)) {
                throw new ForbiddenHttpException('You are not allowed to apply this submitted draft.');
            }

            // Craft applies some drafts using a less strict scenario, so submitted drafts must fail closed here.
            $event->draft->setScenario(Element::SCENARIO_LIVE);

            if (!$event->draft->validate()) {
                throw new InvalidElementException($event->draft);
            }

            $review = $submissionsService->createReview($submission, $event->draft);
            $review->role = Review::ROLE_PUBLISHER;
            $review->status = Review::STATUS_APPROVED;

            if (!$review->validate()) {
                Craft::$app->getUrlManager()->setRouteParams([
                    'submission' => $submission,
                    'review' => $review,
                ]);

                throw new InvalidElementException($event->draft, Craft::t('workflow', 'Could not save review.'));
            }

            $this->_draftApproval = [
                'submission' => $submission,
                'review' => $review,
                'ownerId' => $event->draft->getCanonicalId(),
                'siteId' => $event->draft->siteId,
                'draftElementId' => $event->draft->id,
                'expectedReviewId' => $expectedReviewId,
                'lockName' => $lockName,
                'approved' => false,
            ];
        } catch (Throwable $e) {
            $mutex->release($lockName);
            throw $e;
        }
    }

    public function onAfterSaveAppliedDraft(ElementEvent $event): void
    {
        $context = $this->_draftApproval;

        if (
            $context === null ||
            !($event->element instanceof Entry) ||
            $event->element->getIsDraft() ||
            $event->element->getCanonicalId() !== $context['ownerId'] ||
            $event->element->siteId !== $context['siteId']
        ) {
            return;
        }

        try {
            $approved = Workflow::$plugin->getSubmissions()->approveSubmissionForApplication(
                $event->element,
                $context['submission'],
                $context['review'],
                $context['expectedReviewId'],
            );
        } catch (Throwable $e) {
            $this->_releaseDraftApprovalLock($context);
            $this->_draftApproval = null;
            throw $e;
        }

        if (!$approved) {
            $this->_releaseDraftApprovalLock($context);
            $this->_draftApproval = null;
            throw new InvalidElementException($event->element, Craft::t('workflow', 'Could not approve and publish.'));
        }

        $this->_draftApproval['approved'] = true;
    }

    public function onAfterApplyDraft(DraftEvent $event): void
    {
        $context = $this->_draftApproval;
        $this->_draftApproval = null;

        if ($context === null) {
            return;
        }

        try {
            if (
                !$context['approved'] ||
                !($event->canonical instanceof Entry) ||
                $event->canonical->getCanonicalId() !== $context['ownerId'] ||
                $event->canonical->siteId !== $context['siteId'] ||
                $event->draft->id !== $context['draftElementId']
            ) {
                return;
            }

            Workflow::$plugin->getSubmissions()->finalizeAppliedSubmission(
                $event->canonical,
                $context['submission'],
                $context['review'],
            );
        } finally {
            $this->_releaseDraftApprovalLock($context);
        }
    }

    public function onCreateFieldLayoutForm(CreateFieldLayoutFormEvent $event)
    {
        $settings = Workflow::$plugin->getSettings();

        if (!($event->element instanceof Entry)) {
            return;
        }

        if (!$settings->lockDraftSubmissions) {
            return;
        }

        // Check to see if there's a matching pending (submitted) Workflow submission
        $submission = Submission::find()
            ->ownerId($event->element->getCanonicalId())
            ->ownerSiteId($event->element->siteId)
            ->ownerDraftId($event->element->draftId)
            ->limit(1)
            ->isComplete(false)
            ->isPending(true)
            ->one();

        if (!$submission) {
            return;
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser || !Workflow::$plugin->getSubmissionPermissions()->canEditDraft($currentUser, $event->element, $submission)) {
            $event->static = true;
        }
    }

    public function renderEntrySidebar(DefineHtmlEvent $event): void
    {
        $entry = $event->sender;

        $settings = Workflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $editorGroup = $settings->getEditorUserGroup($entry->site);
        $publisherGroup = $settings->getPublisherUserGroup($entry->site);

        if (!$editorGroup || !$publisherGroup) {
            Workflow::info('Editor and Publisher groups not set in settings.');

            return;
        }

        if (!$currentUser) {
            Workflow::info('No current user.');

            return;
        }

        // If the user is in _both_ editor and publisher groups, work it out.
        if ($currentUser->isInGroup($editorGroup) && $currentUser->isInGroup($publisherGroup)) {
            $submissions = $this->_getSubmissionsFromContext($entry);

            $pendingSubmissionsFromOthers = ArrayHelper::where($submissions, function($submission) use ($currentUser) {
                return $submission->status === 'pending' && $submission->editorId != $currentUser->id;
            }, true, true, false);

            $pendingOwnAllowed = false;

            foreach ($submissions as $submission) {
                if ($submission->status === 'pending' && $submission->editorId == $currentUser->id) {
                    if (Workflow::$plugin->getSubmissions()->canUserApproveOwnSubmission($currentUser, $submission)) {
                        $pendingOwnAllowed = true;
                        break;
                    }
                }
            }

            if ($pendingSubmissionsFromOthers || $pendingOwnAllowed) {
                $event->html .= $this->_renderEntrySidebarPanel($entry, 'publisher-pane');
                return;
            }

            $event->html .= $this->_renderEntrySidebarPanel($entry, 'editor-pane');
            return;
        }

        // If the user is in _both_ editor and reviewer groups, work it out.
        if ($currentUser->isInGroup($editorGroup)) {
            $inReviewerGroup = false;

            // As there are multiple reviewer groups, check the next one.
            $submissions = $this->_getSubmissionsFromContext($entry);
            $lastSubmission = empty($submissions) ? null : end($submissions);

            foreach (Workflow::$plugin->getSubmissions()->getReviewerUserGroups($entry->site, $lastSubmission) as $userGroup) {
                if ($currentUser->isInGroup($userGroup)) {
                    $inReviewerGroup = true;
                }
            }

            if ($inReviewerGroup) {
                $pendingSubmissions = ArrayHelper::where($submissions, function($submission) use ($currentUser) {
                    // Lack of same-user check, to allow editor+reviewers to review their own work.
                    return $submission->status === 'pending';
                }, true, true, false);

                if ($pendingSubmissions) {
                    $event->html .= $this->_renderEntrySidebarPanel($entry, 'reviewer-pane');
                    return;
                }

                $event->html .= $this->_renderEntrySidebarPanel($entry, 'editor-pane');
                return;
            }
        }

        // Show the sidebar submission button for editors
        if ($currentUser->isInGroup($editorGroup)) {
            $event->html .= $this->_renderEntrySidebarPanel($entry, 'editor-pane');
            return;
        }

        // Show another information panel for publishers (if there's submission info)
        if ($currentUser->isInGroup($publisherGroup)) {
            $event->html .= $this->_renderEntrySidebarPanel($entry, 'publisher-pane');
            return;
        }

        // Show the sidebar submission button for reviewers
        $submissions = $this->_getSubmissionsFromContext($entry);
        $lastSubmission = empty($submissions) ? null : end($submissions);

        foreach (Workflow::$plugin->getSubmissions()->getReviewerUserGroups($entry->site, $lastSubmission) as $userGroup) {
            if ($currentUser->isInGroup($userGroup)) {
                $event->html .= $this->_renderEntrySidebarPanel($entry, 'reviewer-pane');
                return;
            }
        }
    }


    // Private Methods
    // =========================================================================

    private function _renderEntrySidebarPanel($entry, $template): ?string
    {
        $settings = Workflow::$plugin->getSettings();

        if (!Workflow::$plugin->getSubmissionPermissions()->isSectionEnabled($entry)) {
            Workflow::info('New enabled sections.');

            return null;
        }

        // Get existing submissions
        $submissions = $this->_getSubmissionsFromContext($entry);

        // Merge any additional route params
        $routeParams = Craft::$app->getUrlManager()->getRouteParams();
        unset($routeParams['template'], $routeParams['template']);

        return Craft::$app->getView()->renderTemplate('workflow/_sidebar/' . $template, array_merge([
            'entry' => $entry,
            'submissions' => $submissions,
            'settings' => $settings,
        ], $routeParams));
    }

    private function _getSubmissionsFromContext($entry): array
    {
        // Get existing submissions
        $ownerId = $entry->getCanonicalId() ?? ':empty:';
        $draftId = $entry->draftId ?? ':empty:';
        $siteId = $entry->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;

        // Always refer to the canonical entry (the entry itself if non-draft, or the draft's parent)
        $submissions = Submission::find()
            ->ownerId($ownerId)
            ->siteId($siteId)
            ->ownerSiteId($siteId)
            ->ownerDraftId($draftId)
            ->all();

        return $submissions;
    }

    private function _denyEntrySave(ModelEvent $event, string $message): void
    {
        $this->_releaseEntryActionLock();
        $event->isValid = false;
        $event->sender->addError('error', $message);
    }

    private function _acquireEntryActionLock(string $lockName): bool
    {
        $mutex = Craft::$app->getMutex();

        if ($this->_entryActionLockName === $lockName && $mutex->isAcquired($lockName)) {
            return true;
        }

        $this->_releaseEntryActionLock();

        if (!$mutex->acquire($lockName, Submissions::TRANSITION_LOCK_TIMEOUT)) {
            return false;
        }

        $this->_entryActionLockName = $lockName;
        return true;
    }

    private function _releaseEntryActionLock(): void
    {
        if ($this->_entryActionLockName === null) {
            return;
        }

        $lockName = $this->_entryActionLockName;
        $this->_entryActionLockName = null;
        $mutex = Craft::$app->getMutex();

        if ($mutex->isAcquired($lockName)) {
            $mutex->release($lockName);
        }
    }

    private function _getExpectedReviewId(?Submission $submission): ?int
    {
        $reviewId = Craft::$app->getRequest()->getBodyParam('workflowReviewId');

        if (is_numeric($reviewId) && (int)$reviewId > 0) {
            return (int)$reviewId;
        }

        return $submission ? Workflow::$plugin->getSubmissions()->getLatestReviewId($submission) : null;
    }

    private function _releaseDraftApprovalLock(array $context): void
    {
        $mutex = Craft::$app->getMutex();

        if ($mutex->isAcquired($context['lockName'])) {
            $mutex->release($context['lockName']);
        }
    }
}
