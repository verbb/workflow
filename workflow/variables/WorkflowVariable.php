<?php
namespace Craft;

class WorkflowVariable
{
    public function getPlugin()
    {
        return craft()->plugins->getPlugin('workflow');
    }

    public function getPluginUrl()
    {
        return $this->getPlugin()->getPluginUrl();
    }

    public function getPluginName()
    {
        return $this->getPlugin()->getName();
    }

    public function getPluginVersion()
    {
        return $this->getPlugin()->getVersion();
    }
}
