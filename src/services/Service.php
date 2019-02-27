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
use craft\helpers\Db;
use craft\helpers\UrlHelper;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function onBeforeSaveEntry(ModelEvent $event)
    {
        $settings = Workflow::$plugin->getSettings();
        $action = Craft::$app->getRequest()->getBodyParam('workflow-action');

        $editorNotes = Craft::$app->getRequest()->getBodyParam('editorNotes');
        $publisherNotes = Craft::$app->getRequest()->getBodyParam('publisherNotes');

        if ($action === 'save-submission') {
            // Content validation won't trigger unless its set to 'live' - but that won't happen because an editor
            // can't publish. We quickly switch it on to make sure the entry validates correctly.
            $event->sender->setScenario(Element::SCENARIO_LIVE);
            $event->sender->validate();

            // We also need to validate notes fields, if required before we save the entry
            if ($settings->editorNotesRequired && !$editorNotes) {
                $event->isValid = false;

                Craft::$app->getUrlManager()->setRouteParams([
                    'editorNotesErrors' => [Craft::t('workflow', 'Editor notes are required')],
                ]);
            }
        }

        if ($action === 'approve-submission') {
            // If we are approving a submission, make sure to make it live
            $event->sender->enabled = true;
        }

        if ($action === 'approve-submission' || $action === 'reject-submission') {
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
        $action = Craft::$app->getRequest()->getBodyParam('workflow-action');
        $redirect = Craft::$app->getRequest()->getBodyParam('redirect');

        if (!$action || $event->sender->propagating || isset($event->sender->draftId)) {
            return;
        }

        Craft::$app->runAction('workflow/submissions/' . $action, ['entry' => $event->sender]);

        $url = Craft::$app->getView()->renderObjectTemplate($redirect, $event->sender);
        $url = UrlHelper::url($url);

        return Craft::$app->getResponse()->redirect($url, 302)->send();
    }

    public function onBeforeSaveEntryDraft(DraftEvent $event)
    {
        $settings = Workflow::$plugin->getSettings();
        $action = Craft::$app->getRequest()->getBodyParam('workflow-action');

        $editorNotes = Craft::$app->getRequest()->getBodyParam('editorNotes');
        $publisherNotes = Craft::$app->getRequest()->getBodyParam('publisherNotes');

        if ($action === 'save-submission') {
            // We also need to validate notes fields, if required before we save the entry
            if ($settings->editorNotesRequired && !$editorNotes) {
                $event->handled = true;

                Craft::$app->getUrlManager()->setRouteParams([
                    'editorNotesErrors' => [Craft::t('workflow', 'Editor notes are required')],
                ]);
            }
        }

        if ($action === 'approve-submission' || $action === 'reject-submission') {
            // We also need to validate notes fields, if required before we save the entry
            if ($settings->publisherNotesRequired && !$publisherNotes) {
                $event->handled = true;

                Craft::$app->getUrlManager()->setRouteParams([
                    'publisherNotesErrors' => [Craft::t('workflow', 'Publisher notes are required')],
                ]);

                return false;
            }
        }
    }

    public function onAfterSaveEntryDraft(DraftEvent $event)
    {
        $action = Craft::$app->getRequest()->getBodyParam('workflow-action');
        $redirect = Craft::$app->getRequest()->getBodyParam('redirect');

        if (!$action) {
            return;
        }

        Craft::$app->runAction('workflow/submissions/' . $action, ['draft' => $event->draft]);

        $url = Craft::$app->getView()->renderObjectTemplate($redirect, $event->draft);
        $url = UrlHelper::url($url);

        return Craft::$app->getResponse()->redirect($url, 302)->send();
    }

    public function onAfterPublishEntryDraft(DraftEvent $event)
    {
        $action = Craft::$app->getRequest()->getBodyParam('workflow-action');

        if (!$action) {
            return;
        }

        Craft::$app->runAction('workflow/submissions/' . $action, ['draft' => $event->draft]);

        // Approving a draft should redirect properly
        $redirect = $event->draft->getCpEditUrl();

        return Craft::$app->getResponse()->redirect($redirect, 302)->send();
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
            ->draftId($draftId)
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