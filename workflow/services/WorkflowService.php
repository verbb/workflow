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
            craft()->templates->hook('cp.entries.edit.right-pane', function(&$context) {
                if (!$context['entry']->id) {
                    return;
                }

                // See if there's an existing submission
                $submission = craft()->workflow_submissions->getByElementId($context['entry']->id);

                return craft()->templates->render('workflow/_includes/editor-pane', array(
                    'context' => $context,
                    'submission' => $submission,
                ));
            });
        }

        // Show another information panel for publishers (if there's submission info)
        if ($user->isInGroup($publisherGroup)) {
            craft()->templates->hook('cp.entries.edit.right-pane', function(&$context) {
                if (!$context['entry']->id) {
                    return;
                }

                // See if there's an existing submission
                $submission = craft()->workflow_submissions->getByElementId($context['entry']->id);

                return craft()->templates->render('workflow/_includes/publisher-pane', array(
                    'context' => $context,
                    'submission' => $submission,
                ));
            });
        }
    }
    

}