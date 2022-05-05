<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Table;
use craft\events\DefineHtmlEvent;
use craft\events\DraftEvent;
use craft\events\ModelEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\ArrayHelper;
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

        $editorNotes = $request->getBodyParam('editorNotes');
        $reviewerNotes = $request->getBodyParam('reviewerNotes');
        $publisherNotes = $request->getBodyParam('publisherNotes');

        // Disable auto-save for an entry that has been submitted. Only real way to do this.
        // Check to see if this is a draft first
        if (!$action && $event->sender->getIsDraft()) {
            // Check to see if there's a matching pending (submitted) Workflow submission
            $submission = Submission::find()
                ->ownerId($event->sender->id)
                ->ownerSiteId($event->sender->siteId)
                ->ownerDraftId($event->sender->draftId)
                ->limit(1)
                ->status('pending')
                ->orderBy('dateCreated desc')
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
            // Don't trigger for propagating elements
            if ($event->sender->propagating) {
                return;
            }

            // Content validation won't trigger unless its set to 'live' - but that won't happen because an editor
            // can't publish. We quickly switch it on to make sure the entry validates correctly.
            $event->sender->setScenario(Element::SCENARIO_LIVE);

            // Ensure to reset the draft state back to a provisional draft, which has already been switched at this
            // point by `entry-revisions/save-draft`
            if (!$event->sender->validate()) {
                $event->sender->isProvisionalDraft = true;
            }
        }

        if ($action === 'approve-submission') {
            // Don't trigger for propagating elements
            if ($event->sender->propagating) {
                return;
            }

            // For multi-sites, we only want to act on the current site's entry. Returning early will respect the
            // section defaults for enabling the entry per-site.
            if (Craft::$app->getIsMultiSite()) {
                $currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;

                if ($siteHandle = $request->getParam('site')) {
                    $currentSiteId = Craft::$app->getSites()->getSiteByHandle($siteHandle)->id;
                }

                if ($event->sender->siteId != $currentSiteId) {
                    return;
                }
            }

            $event->sender->enabled = true;
            $event->sender->enabledForSite = true;
            $event->sender->setScenario(Element::SCENARIO_LIVE);

            if (($postDate = $request->getBodyParam('postDate')) !== null) {
                $event->sender->postDate = DateTimeHelper::toDateTime($postDate) ?: new DateTime();
            }
        }
    }

    public function onAfterSaveEntry(ModelEvent $event): void
    {
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        // When approving, we don't want to perform an action here - wait until the draft has been applied
        if (!$action || $event->sender->propagating || $event->isNew || $this->afterSaveRun) {
            return;
        }

        // This helps us maintain whether the after-save event has already been triggered for this
        // request, and not to have it run again. This is most commonly caused by Preparse fields
        // which re-save the element again, straight after it's first save. Then we end up with multiple
        // submissions, created each time it's called.
        $this->afterSaveRun = true;

        // Check if we're submitting a new submission
        if ($action == 'save-submission') {
            Workflow::$plugin->getSubmissions()->saveSubmission($event->sender);

            // This doesn't seem to redirect properly, which is annoying!
            if ($request->getIsCpRequest()) {
                $url = $event->sender->getCpEditUrl();

                if ($event->sender->draftId) {
                    $url = UrlHelper::cpUrl($url, ['draftId' => $event->sender->draftId]);
                }

                Craft::$app->getResponse()->redirect($url)->send();
            }
        }

        if ($action == 'approve-review') {
            Workflow::$plugin->getSubmissions()->approveReview($event->sender);
        }

        if ($action == 'reject-review') {
            Workflow::$plugin->getSubmissions()->rejectReview($event->sender);
        }

        if ($action == 'revoke-submission') {
            Workflow::$plugin->getSubmissions()->revokeSubmission($event->sender);
        }

        if ($action == 'reject-submission') {
            Workflow::$plugin->getSubmissions()->rejectSubmission($event->sender);
        }

        // For the cases where it's been submitted from the front-end, it's not a draft!
        if ($action === 'approve-submission') {
            // Probably a better way to deal with this, but at this point, its no longer a draft
            // it's now a fully realised entry. We rely on the query param to determine if this was
            // a draft that was approved and saved, or a regular entry that was approved.
            if (!$request->getParam('draftId')) {
                Workflow::$plugin->getSubmissions()->approveSubmission($event->sender);
            }
        }
    }

    public function onAfterApplyDraft(DraftEvent $event): void
    {
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        if (!$action) {
            return;
        }

        // At this point, the draft entry has already been deleted, and our submissions' ownerId set to null
        // We want to keep the link, so we need to supply the source, not the draft.
        if ($action == 'approve-submission' || $action == 'approve-only-submission') {
            Workflow::$plugin->getSubmissions()->approveSubmission($event->canonical);
        }
    }

    public function handleSiteRequest($event, $action): void
    {
        if (!$action || $event->sender->propagating || ElementHelper::isDraftOrRevision($event->sender)) {
            return;
        }

        // When we're saving a brand-new entry for submission, we need to create a new draft
        // and work with that, as opposed to the original entry.
        if ($action == 'save-submission') {
            // Perform the Workflow submission on this new entry
            Workflow::$plugin->getSubmissions()->saveSubmission($event->sender);
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
            Workflow::log('Editor and Publisher groups not set in settings.');

            return;
        }

        if (!$currentUser) {
            Workflow::log('No current user.');

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

        Workflow::log('Try to render ' . $template);

        // Make sure workflow is enabled for this section - or all section
        if (!$settings->enabledSections) {
            Workflow::log('New enabled sections.');

            return null;
        }

        if ($settings->enabledSections != '*') {
            $enabledSectionIds = Db::idsByUids(Table::SECTIONS, $settings->enabledSections);

            if (!in_array($entry->sectionId, $enabledSectionIds)) {
                Workflow::log('Entry not in allowed section.');

                return null;
            }
        }

        // Get existing submissions
        $submissions = $this->_getSubmissionsFromContext($entry);

        Workflow::log('Rendering ' . $template . ' for #' . $entry->id);

        // Merge any additional route params
        $routeParams = Craft::$app->getUrlManager()->getRouteParams();
        unset($routeParams['template'], $routeParams['template']);

        return Craft::$app->view->renderTemplate('workflow/_includes/' . $template, array_merge([
            'entry' => $entry,
            'submissions' => $submissions,
            'settings' => $settings,
        ], $routeParams));
    }

    private function _getSubmissionsFromContext($entry): array
    {
        // Get existing submissions
        $ownerId = $entry->id ?? ':empty:';
        $draftId = $entry->draftId ?? ':empty:';
        $siteId = $entry->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;

        return Submission::find()
            ->ownerId($ownerId)
            ->ownerSiteId($siteId)
            ->ownerDraftId($draftId)
            ->all();
    }
}
