<?php
namespace Craft;

class Workflow_SubmissionsWidget extends BaseWidget
{
    // Public Methods
    // =========================================================================

    public function getTitle()
    {
        return Craft::t('Workflow Submissions');
    }
    
    public function getName()
    {
        return Craft::t($this->getTitle());
    }
    
    public function getIconPath()
    {
        return craft()->path->getPluginsPath() . 'workflow/resources/icon-mask.svg';
    }
    
    public function getBodyHtml()
    {
        $settings = $this->getSettings();

        $criteria = craft()->workflow_submissions->getCriteria();
        $criteria->status = $settings->status;
        $criteria->limit = $settings->limit;

        $submissions = $criteria->find();

        return craft()->templates->render('workflow/_widget/body', array(
            'submissions' => $submissions,
        ));
    }
    
    public function getSettingsHtml()
    {
        return craft()->templates->render('workflow/_widget/settings', array(
            'settings' => $this->getSettings(),
        ));
    }


    // Protected Methods
    // =========================================================================

    protected function defineSettings()
    {
        return array(
            'status' => array(AttributeType::String, 'default' => 'pending'),
            'limit' => array(AttributeType::Number, 'default' => 10),
        );
    }
    
}