<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Component;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function renderEntrySidebar(&$context)
    {
        $settings = Workflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$settings->editorUserGroup || !$settings->publisherUserGroup) {
            Workflow::log('Editor and Publisher groups not set in settings.');

            return;
        }

        $editorGroup = Craft::$app->userGroups->getGroupById($settings->editorUserGroup);
        $publisherGroup = Craft::$app->userGroups->getGroupById($settings->publisherUserGroup);

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

        if (!$context['entry']->id) {
            Workflow::log('New entry.');

            return;
        }

        // Make sure workflow is enabled for this section - or all section
        if (!$settings->enabledSections) {
            Workflow::log('New enabled sections.');

            return;
        }

        if ($settings->enabledSections != '*') {
            if (!in_array($context['entry']->sectionId, $settings->enabledSections)) {
                Workflow::log('Entry not in allowed section.');

                return;
            }
        }

        // See if there's an existing submission
        $draftId = (isset($context['draftId'])) ? $context['draftId'] : ':empty:';
        $siteId = (isset($context['entry']['siteId'])) ? $context['entry']['siteId'] : Craft::$app->getSites()->getCurrentSite()->id;
        $submissions = Submission::find()
            ->ownerId($context['entry']->id)
            ->ownerSiteId($siteId)
            ->draftId($draftId)
            ->all();

        Workflow::log('Rendering ' . $template . ' for #' . $context['entry']->id);

        return Craft::$app->view->renderTemplate('workflow/_includes/' . $template, [
            'context' => $context,
            'submissions' => $submissions,
        ]);
    }

}