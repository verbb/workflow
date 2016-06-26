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
        return '0.9.0';
    }

    public function getSchemaVersion()
    {
        return '0.9.0';
    }

    public function getDeveloper()
    {
        return 'S. Group';
    }

    public function getDeveloperUrl()
    {
        return 'http://sgroup.com.au';
    }

    public function getPluginUrl()
    {
        return 'https://github.com/engram-design/Workflow';
    }

    public function getDocumentationUrl()
    {
        return $this->getPluginUrl() . '/blob/master/README.md';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/engram-design/Workflow/master/changelog.json';
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
            'workflow/settings' => array('action' => 'workflow/settings'),
        );
    }

    protected function defineSettings()
    {
        return array(
            'enabledSections' => AttributeType::Mixed,
            'editorUserGroup' => AttributeType::String,
            'publisherUserGroup' => AttributeType::String,
        );
    }

    public function onBeforeInstall()
    {   
        if (version_compare(craft()->getVersion(), '2.5', '<')) {
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
        );
    }
}
