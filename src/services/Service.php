<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Table;
use craft\elements\Entry;
use craft\events\DraftEvent;
use craft\events\ModelEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use DateTime;

use yii\base\ModelEvent as YiiModelEvent;

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
                ->exists();

            if ($submission) {
                $event->isValid = false;

                $event->sender->addError('error', Craft::t('workflow', 'Unable to edit entry once it has been submitted for review.'));
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
            $valid = $event->sender->validate();

            // We also need to validate notes fields, if required before we save the entry
            if ($settings->editorNotesRequired && !$editorNotes) {
                $event->isValid = false;

                Craft::$app->getUrlManager()->setRouteParams([
                    'editorNotesErrors' => [Craft::t('workflow', 'Editor notes are required')],
                ]);
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

        if ($action === 'approve-submission' || $action === 'approve-only-submission' || $action === 'reject-submission') {
            // We also need to validate notes fields, if required before we save the entry
            if ($settings->publisherNotesRequired && !$publisherNotes) {
                $event->isValid = false;

                Craft::$app->getUrlManager()->setRouteParams([
                    'publisherNotesErrors' => [Craft::t('workflow', 'Publisher notes are required')],
                ]);
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

        if ($action == 'revoke-submission') {
            Workflow::$plugin->getSubmissions()->revokeSubmission();
        }

        if ($action == 'reject-submission') {
            Workflow::$plugin->getSubmissions()->rejectSubmission();
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
            // Craft::dump('test');
            $draft = Craft::$app->getDrafts()->createDraft($event->sender, Craft::$app->getUser()->getId());

            // // Perform the Workflow submission on this new draft
            Workflow::$plugin->getSubmissions()->saveSubmission($draft);
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

        $editorGroup = Craft::$app->userGroups->getGroupByUid($settings->editorUserGroup);
        $publisherGroup = Craft::$app->userGroups->getGroupByUid($settings->publisherUserGroup);

        if (!$currentUser) {
            Workflow::log('No current user.');

            return;
        }

        // Only show the sidebar submission button for editors
        if ($currentUser->isInGroup($editorGroup)) {
            return $this->_renderEntrySidebarPanel($context, 'editor-pane');
        }

        // Show another information panel for publishers (if there's submission info)
        if ($currentUser->isInGroup($publisherGroup)) {
            return $this->_renderEntrySidebarPanel($context, 'publisher-pane');
        }
    }


    // Private Methods
    // =========================================================================

    private function _renderEntrySidebarPanel($context, $template)
    {
        $settings = Workflow::$plugin->getSettings();

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

        return Craft::$app->view->renderTemplate('workflow/_includes/' . $template, array_merge([
            'context' => $context,
            'submissions' => $submissions,
            'settings' => $settings,
        ], $routeParams));
    }

}