<?php
namespace Craft;

class Workflow_SubmissionModel extends BaseElementModel
{
    // Properties
    // =========================================================================

    protected $elementType = 'Workflow_Submission';

    const APPROVED  = 'approved';
    const PENDING   = 'pending';
    const REJECTED  = 'rejected';
    const REVOKED   = 'revoked';


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        if ($this->ownerId) {
            return $this->owner->title;
        } else {
            return '';
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCpEditUrl()
    {
        if ($this->draftId) {
            $url = $this->owner->cpEditUrl . '/drafts/' . $this->draftId;
        } else {
            $url = $this->owner->cpEditUrl;
        }

        return $url;
    }

    public function getOwner()
    {
        if ($this->ownerId) {
            return craft()->entries->getEntryById($this->ownerId);
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
        return array_merge(parent::defineAttributes(), array(
            'id'            => array(AttributeType::Number),
            'ownerId'       => array(AttributeType::Number),
            'draftId'       => array(AttributeType::Number),
            'editorId'      => array(AttributeType::Number),
            'publisherId'   => array(AttributeType::Number),
            'status'        => array(AttributeType::Enum, 'values' => array(
                Workflow_SubmissionModel::APPROVED,
                Workflow_SubmissionModel::PENDING,
                Workflow_SubmissionModel::REJECTED,
                Workflow_SubmissionModel::REVOKED,
            )),
            'notes'         => array(AttributeType::Mixed),
            'dateApproved'  => array(AttributeType::DateTime),
            'dateRejected'  => array(AttributeType::DateTime),
            'dateRevoked'   => array(AttributeType::DateTime),
            'dateCreated'   => array(AttributeType::DateTime),
            'dateUpdated'   => array(AttributeType::DateTime),
        ));
    }

}