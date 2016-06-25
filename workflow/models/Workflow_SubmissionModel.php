<?php
namespace Craft;

class Workflow_SubmissionModel extends BaseModel
{
    // Public Methods
    // =========================================================================

    public function getElement()
    {
        if ($this->elementId) {
            return craft()->entries->getEntryById($this->elementId);
        }
    }

    public function getEditor()
    {
        if ($this->editorId) {
            return craft()->users->getUserById($this->editorId);
        }
    }

    public function getPublisher()
    {
        if ($this->publisherId) {
            return craft()->users->getUserById($this->publisherId);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return array(
            'id'            => array(AttributeType::Number),
            'elementId'     => array(AttributeType::Number),
            'editorId'      => array(AttributeType::Number),
            'publisherId'   => array(AttributeType::Number),
            'approved'      => array(AttributeType::Bool),
            'dateApproved'  => array(AttributeType::DateTime),
            'dateCreated'   => array(AttributeType::DateTime),
            'dateUpdated'   => array(AttributeType::DateTime),
        );
    }

}