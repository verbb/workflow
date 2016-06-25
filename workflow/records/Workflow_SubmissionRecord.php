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
            'editor' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE),
            'publisher' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE),
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('elementId', 'editorId'), 'unique' => true),
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
