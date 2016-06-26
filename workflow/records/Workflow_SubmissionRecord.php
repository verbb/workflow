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
            'element'  => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
            'draft'  => array(static::BELONGS_TO, 'EntryDraftRecord', 'required' => false, 'onDelete' => static::CASCADE),
            'editor' => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::CASCADE),
            'publisher' => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::CASCADE),
        );
    }


    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return array(
            'approved'      => array(AttributeType::Bool),
            'dateApproved'  => array(AttributeType::DateTime),
        );
    }
}
