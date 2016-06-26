<?php
namespace Craft;

class WorkflowService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    public function getPlugin()
    {
        return craft()->plugins->getPlugin('workflow');
    }

    public function getSettings()
    {
        return $this->getPlugin()->getSettings();
    }

    public function renderEntrySidebar()
    {
        $settings = $this->getSettings();
        $user = craft()->userSession->getUser();
        $editorGroup = craft()->userGroups->getGroupById($settings->editorUserGroup);
        $publisherGroup = craft()->userGroups->getGroupById($settings->publisherUserGroup);

        if (!$user) {
            return false;
        }

        // Only show the sidebar submission button for editors
        if ($user->isInGroup($editorGroup)) {
            $this->_renderEntrySidebarPanel('editor-pane');
        }

        // Show another information panel for publishers (if there's submission info)
        if ($user->isInGroup($publisherGroup)) {
            $this->_renderEntrySidebarPanel('publisher-pane');
        }
    }



    // Private Methods
    // =========================================================================

    private function _renderEntrySidebarPanel($template)
    {
        $settings = $this->getSettings();

        craft()->templates->hook('cp.entries.edit.right-pane', function(&$context) use ($settings, $template) {
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
            $draftId = (isset($context['draftId'])) ? $context['draftId'] : null;
            $submissions = craft()->workflow_submissions->getAllByElementId($context['entry']->id, $draftId);

            return craft()->templates->render('workflow/_includes/' . $template, array(
                'context' => $context,
                'submissions' => $submissions,
            ));
        });
    }

}