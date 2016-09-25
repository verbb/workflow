<?php
namespace Craft;

class m160715_000000_workflow_addRejectedStatus extends BaseMigration
{
    public function safeUp()
    {
        // Set fields to be Enum before things get too complicated!
        craft()->db->createCommand()->alterColumn('workflow_submissions', 'status', array(
            'values' => array(
                Workflow_SubmissionModel::APPROVED,
                Workflow_SubmissionModel::PENDING,
                Workflow_SubmissionModel::REJECTED,
                Workflow_SubmissionModel::REVOKED,
            ),
            'column' => 'enum'
        ));

        craft()->db->createCommand()->addColumnAfter('workflow_submissions', 'dateRevoked', ColumnType::DateTime, 'dateApproved');

        craft()->db->createCommand()->addColumnAfter('workflow_submissions', 'dateRejected', ColumnType::DateTime, 'dateApproved');

        craft()->db->createCommand()->addColumnAfter('workflow_submissions', 'notes', ColumnType::Text, 'status');

        return true;
    }
}
