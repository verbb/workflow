<?php
namespace Craft;

class WorkflowPlugin extends BasePlugin
{
    // =========================================================================
    // PLUGIN INFO
    // =========================================================================

    public function getName()
    {
        return Craft::t('Workflow');
    }

    public function getVersion()
    {
        return '0.9.6';
    }

    public function getSchemaVersion()
    {
        return '0.9.3';
    }

    public function getDeveloper()
    {
        return 'Verbb';
    }

    public function getDeveloperUrl()
    {
        return 'https://verbb.io';
    }

    public function getPluginUrl()
    {
        return 'https://github.com/verbb/workflow';
    }

    public function getDocumentationUrl()
    {
        return 'https://verbb.io/craft-plugins/workflow/docs';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/verbb/workflow/craft-2/changelog.json';
    }

    public function hasCpSection()
    {
        return true;
    }

    public function getSettingsUrl()
    {
        return 'workflow/settings';
    }

    public function registerCpRoutes()
    {
        return array(
            'workflow/drafts' => array('action' => 'workflow/drafts'),
            'workflow/settings' => array('action' => 'workflow/settings'),
        );
    }

    protected function defineSettings()
    {
        return array(
            'enabledSections' => AttributeType::Mixed,
            'editorUserGroup' => AttributeType::String,
            'publisherUserGroup' => AttributeType::String,
            'editorNotifications' => array( AttributeType::Bool, 'default' => true ),
            'publisherNotifications' => array( AttributeType::Bool, 'default' => true ),
            'selectedPublishers' => AttributeType::Mixed,
        );
    }

    public function onBeforeInstall()
    {
        $version = craft()->getVersion();

        // Craft 2.6.2951 deprecated `craft()->getBuild()`, so get the version number consistently
        if (version_compare(craft()->getVersion(), '2.6.2951', '<')) {
            $version = craft()->getVersion() . '.' . craft()->getBuild();
        }

        if (version_compare($version, '2.5', '<')) {
            throw new Exception($this->getName() . ' requires Craft CMS 2.5+ in order to run.');
        }
    }


    // =========================================================================
    // HOOKS
    // =========================================================================

    public function init()
    {
        if (craft()->request->isCpRequest()) {
            // Add template to the sidebar for maximum trendyness
            craft()->workflow->renderEntrySidebar();
        }
    }

    public function registerEmailMessages()
    {
        return array(
            'workflow_publisher_notification',
            'workflow_editor_notification',
        );
    }
}
