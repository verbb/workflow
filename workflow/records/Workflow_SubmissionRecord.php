<?php
namespace Craft;

class Workflow_SubmissionRecord extends BaseRecord
{
    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'workflow_submissions';
    }

    public function defineRelations()
    {
        return array(
            'owner'  => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
            'draft'  => array(static::BELONGS_TO, 'EntryDraftRecord', 'required' => false, 'onDelete' => static::CASCADE),
            'editor' => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::CASCADE),
            'publisher' => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::CASCADE),
        );
    }

    public function scopes()
    {
        return array(
            'ordered' => array('order' => 'dateCreated'),
        );
    }


    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return array(
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
        );
    }
}
