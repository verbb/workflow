<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\helpers\StringHelper;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Table;
use craft\elements\Entry;
use craft\events\DefineHtmlEvent;
use craft\events\DraftEvent;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;

use DateTime;

class Service extends Component
{
    // Properties
    // =========================================================================

    public bool $afterSaveRun = false;


    // Public Methods
    // =========================================================================

    public function onBeforeSaveEntry(ModelEvent $event): void
    {
        $settings = Workflow::$plugin->getSettings();
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        // Don't trigger for propagating elements
        if ($event->sender->propagating) {
            return;
        }

        $currentSite = Craft::$app->getSites()->getCurrentSite();

        // Sanitize notes first
        $workflowNotes = StringHelper::sanitizeNotes((string)$request->getBodyParam('workflowNotes'));

        // Save the notes for later, due to a number of different events triggering
        Craft::$app->getUrlManager()->setRouteParams([
            'workflowNotes' => StringHelper::unSanitizeNotes($workflowNotes),
        ]);
        
        // Disable auto-save for an entry that has been submitted. Only real way to do this.
        // Check to see if this is a draft first
        if (!$action && $event->sender->getIsDraft()) {
            // Check to see if there's a matching pending (submitted) Workflow submission
            $submission = Submission::find()
                ->ownerId($event->sender->getCanonicalId())
                ->ownerSiteId($event->sender->siteId)
                ->ownerDraftId($event->sender->draftId)
                ->limit(1)
                ->isComplete(false)
                ->isPending(true)
                ->one();

            if ($submission !== null) {
                $currentUser = Craft::$app->getUser()->getIdentity();

                // Ensure current user is allowed to review the submission
                // If the current user is the author, they can't edit their own submission
                /** @var Submission $submission */
                if ((!$submission->canUserReview($currentUser, $event->sender->site) || $submission->editorId == $currentUser->id) && $settings->lockDraftSubmissions) {
                    $event->isValid = false;

                    $event->sender->addError('error', Craft::t('workflow', 'Unable to edit entry once it has been submitted for review.'));
                }
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

        if ($action === 'approve-submission') {
            // For multi-sites, we only want to act on the current site's entry. Returning early will respect the
            // section defaults for enabling the entry per-site.
            if (Craft::$app->getIsMultiSite()) {
                $currentSiteId = $currentSite->id;

                if ($siteHandle = $request->getParam('site')) {
                    if ($site = Craft::$app->getSites()->getSiteByHandle($siteHandle)) {
                        $currentSiteId = $site->id;
                    }
                }

                if ($event->sender->siteId != $currentSiteId) {
                    return;
                }
            }

            // Automatically set the entry "live", saving users from having to enable and set a post date manually.
            $event->sender->enabled = true;
            $event->sender->enabledForSite = true;
            $event->sender->setScenario(Element::SCENARIO_LIVE);

            if (($postDate = $request->getBodyParam('postDate')) !== null) {
                $event->sender->postDate = DateTimeHelper::toDateTime($postDate) ?: new DateTime();
            }

            // We also need to validate notes fields, if required before we save the entry
            if ($settings->getPublisherNotesRequired($currentSite) && !$workflowNotes) {
                Craft::$app->getUrlManager()->setRouteParams([
                    'workflowNotesErrors' => [Craft::t('workflow', 'Notes are required')],
                ]);

                $event->isValid = false;
            }
        }
    }

    public function onAfterSaveElement(ElementEvent $event): void
    {
        if (!($event->element instanceof Entry)) {
            return;
        }

        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        // When approving, we don't want to perform an action here - wait until the draft has been applied
        if (!$action || $event->element->propagating || $this->afterSaveRun) {
            return;
        }

        // This helps us maintain whether the after-save event has already been triggered for this
        // request, and not to have it run again. This is most commonly caused by Preparse fields
        // which re-save the element again, straight after it's first save. Then we end up with multiple
        // submissions, created each time it's called.
        $this->afterSaveRun = true;

        // Check if we're submitting a new submission
        if ($action == 'save-submission') {
            // If this is a front-end request, and if this is a draft, don't trigger live mode yet.
            // This is because we need to create the draft first, then save that
            if (Craft::$app->getRequest()->getIsSiteRequest() && $event->element->scenario === Element::SCENARIO_ESSENTIALS) {
                // This is when the draft entry is saved, we want to process this again next time
                $this->afterSaveRun = false;

                return;
            }

            Workflow::$plugin->getSubmissions()->saveSubmission($event->element);
        }

        if ($action == 'approve-review') {
            Workflow::$plugin->getSubmissions()->approveReview($event->element);
        }

        if ($action == 'reject-review') {
            Workflow::$plugin->getSubmissions()->rejectReview($event->element);
        }

        if ($action == 'revoke-submission') {
            Workflow::$plugin->getSubmissions()->revokeSubmission($event->element);
        }

        if ($action == 'reject-submission') {
            Workflow::$plugin->getSubmissions()->rejectSubmission($event->element);
        }

        // For the cases where it's been submitted from the front-end, it's not a draft!
        if ($action === 'approve-submission') {
            Workflow::$plugin->getSubmissions()->approveSubmission($event->element);
        }

        // We're approving-only, so basically the draft is just saved, but the submission lifecycle completed
        if ($action == 'approve-only-submission') {
            Workflow::$plugin->getSubmissions()->approveSubmission($event->element, false);
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
            // Are there any submissions pending for any users but this one?
            $submissions = $this->_getSubmissionsFromContext($entry);

            $pendingSubmissions = ArrayHelper::where($submissions, function($submission) use ($currentUser) {
                return $submission->status === 'pending' && $submission->editorId != $currentUser->id;
            }, true, true, false);

            if ($pendingSubmissions) {
                $event->html .= $this->_renderEntrySidebarPanel($entry, 'publisher-pane');
                return;
            }

            $event->html .= $this->_renderEntrySidebarPanel($entry, 'editor-pane');
            return;
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

        // Make sure workflow is enabled for this section - or all section
        if (!$settings->enabledSections) {
            Workflow::info('New enabled sections.');

            return null;
        }

        if ($settings->enabledSections != '*') {
            $enabledSectionIds = Db::idsByUids(Table::SECTIONS, $settings->enabledSections);

            if (!in_array($entry->sectionId, $enabledSectionIds)) {
                Workflow::info('Entry not in allowed section.');

                return null;
            }
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
}
