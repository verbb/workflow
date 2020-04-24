<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Table;
use craft\events\DraftEvent;
use craft\events\ModelEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;

use DateTime;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function onBeforeSaveEntry(ModelEvent $event)
    {
        $settings = Workflow::$plugin->getSettings();
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        $editorNotes = $request->getBodyParam('editorNotes');
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
                /** @var Submission $submission */
                if (!$submission->canUserReview($currentUser)) {
                    $event->isValid = false;

                    $event->sender->addError('error', Craft::t('workflow', 'Unable to edit entry once it has been submitted for review.'));
                }
            }
        }

        if ($action === 'approve-submission') {
            // Don't trigger for propagating elements
            if ($event->sender->propagating) {
                return;
            }

            // If we are approving a submission, make sure to make it live
            $event->sender->enabled = true;
            $event->sender->enabledForSite = true;
            $event->sender->setScenario(Element::SCENARIO_LIVE);

            if (($postDate = $request->getBodyParam('postDate')) !== null) {
                $event->sender->postDate = DateTimeHelper::toDateTime($postDate) ?: new DateTime();
            }
        }
    }

    public function onAfterSaveEntry(ModelEvent $event)
    {
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        // Special-handling for front-end requests to keep things simple for user templates
        // and without having to deal with entry revisions/drafts
        if ($request->getIsSiteRequest()) {
            $this->handleSiteRequest($event, $action);
        }

        // When approving, we don't want to perform an action here - wait until the draft has been applied
        if (!$action || $event->sender->propagating || $event->isNew) {
            return;
        }

        // Check if we're submitting a new submission on an existing entry - different to a brand-new, unsaved draft
        if ($action == 'save-submission' && !$event->sender->getIsUnsavedDraft()) {
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
            Workflow::$plugin->getSubmissions()->approveReview();
        }

        if ($action == 'reject-review') {
            Workflow::$plugin->getSubmissions()->rejectReview();
        }

        if ($action == 'revoke-submission') {
            Workflow::$plugin->getSubmissions()->revokeSubmission();
        }

        if ($action == 'reject-submission') {
            Workflow::$plugin->getSubmissions()->rejectSubmission();
        }

        // For the cases where its been submitted from the front-end, its not a draft!
        if ($action === 'approve-submission') {
            // Probably a better way to deal with this, but at this point, its no longer a draft
            // its now a fully realised entry. We rely on the query param to determine if this was
            // a draft that was approved and saved, or a regular entry that was approved.
            if (!$request->getParam('draftId')) {
                Workflow::$plugin->getSubmissions()->approveSubmission($event->sender);
            }
        }
    }

    public function onAfterApplyDraft(DraftEvent $event)
    {
        $request = Craft::$app->getRequest();
        $action = $request->getBodyParam('workflow-action');

        if (!$action) {
            return;
        }

        // At this point, the draft entry has already been deleted, and our submissions' ownerId set to null
        // We want to keep the link, so we need to supply the source, not the draft.
        if ($action == 'approve-submission' || $action == 'approve-only-submission') {
            Workflow::$plugin->getSubmissions()->approveSubmission($event->source);
        }
    }

    public function handleSiteRequest($event, $action)
    {
        if (!$action || $event->sender->propagating || ElementHelper::isDraftOrRevision($event->sender)) {
            return;
        }

        // When we're saving a brand new entry for submission, we need to create a new draft
        // and work with that, as opposed to the original entry.
        if ($action == 'save-submission') {
            // Perform the Workflow submission on this new entry
            Workflow::$plugin->getSubmissions()->saveSubmission($event->sender);
        }
    }

    public function renderEntrySidebar(&$context)
    {
        $settings = Workflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$settings->editorUserGroup || !$settings->publisherUserGroup) {
            Workflow::log('Editor and Publisher groups not set in settings.');

            return;
        }

        if (!$currentUser) {
            Workflow::log('No current user.');

            return;
        }

        $editorGroup = Craft::$app->userGroups->getGroupByUid($settings->editorUserGroup);
        $publisherGroup = Craft::$app->userGroups->getGroupByUid($settings->publisherUserGroup);

        // Show the sidebar submission button for editors
        if ($currentUser->isInGroup($editorGroup)) {
            return $this->_renderEntrySidebarPanel($context, 'editor-pane');
        }

        // Show another information panel for publishers (if there's submission info)
        if ($currentUser->isInGroup($publisherGroup)) {
            return $this->_renderEntrySidebarPanel($context, 'publisher-pane');
        }

        // Show the sidebar submission button for reviewers
        foreach ($settings->getReviewerUserGroups() as $userGroup) {
            if ($currentUser->isInGroup($userGroup)) {
                return $this->_renderEntrySidebarPanel($context, 'reviewer-pane');
            }
        }
    }


    // Private Methods
    // =========================================================================

    private function _renderEntrySidebarPanel($context, $template)
    {
        $settings = Workflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        Workflow::log('Try to render ' . $template);

        // Make sure workflow is enabled for this section - or all section
        if (!$settings->enabledSections) {
            Workflow::log('New enabled sections.');

            return;
        }

        if ($settings->enabledSections != '*') {
            $enabledSectionIds = Db::idsByUids(Table::SECTIONS, $settings->enabledSections);

            if (!in_array($context['entry']->sectionId, $enabledSectionIds)) {
                Workflow::log('Entry not in allowed section.');

                return;
            }
        }

        // See if there's an existing submission
        $ownerId = $context['entry']->id ?? ':empty:';
        $draftId = $context['draftId'] ?? ':empty:';
        $siteId = $context['entry']['siteId'] ?? Craft::$app->getSites()->getCurrentSite()->id;

        $submissions = Submission::find()
            ->ownerId($ownerId)
            ->ownerSiteId($siteId)
            ->ownerDraftId($draftId)
            ->all();

        Workflow::log('Rendering ' . $template . ' for #' . $context['entry']->id);

        // Merge any additional route params
        $routeParams = Craft::$app->getUrlManager()->getRouteParams();
        unset($routeParams['template'], $routeParams['template']);

        // Get the editor user groups that can approve
        return Craft::$app->view->renderTemplate('workflow/_includes/' . $template, array_merge([
            'context' => $context,
            'submissions' => $submissions,
            'settings' => $settings,
        ], $routeParams));
    }

}
