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
            return;
        }

        $editorGroup = Craft::$app->userGroups->getGroupById($settings->editorUserGroup);
        $publisherGroup = Craft::$app->userGroups->getGroupById($settings->publisherUserGroup);

        if (!$currentUser) {
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

        if (!$context['entry']->id) {
            return;
        }

        // Make sure workflow is enabled for this section - or all section
        if ($settings->enabledSections != '*') {
            if (!in_array($context['entry']->sectionId, $settings->enabledSections)) {
                return;
            }
        }

        // See if there's an existing submission
        $draftId = (isset($context['draftId'])) ? $context['draftId'] : ':empty:';
        $submissions = Submission::find()
            ->ownerId($context['entry']->id)
            ->draftId($draftId)
            ->all();

        return Craft::$app->view->renderTemplate('workflow/_includes/' . $template, [
            'context' => $context,
            'submissions' => $submissions,
        ]);
    }

}