<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

class m190627_100000_updateDraftId extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'ownerDraftId')) {
            MigrationHelper::renameColumn('{{%workflow_submissions}}', 'draftId', 'ownerDraftId', $this);
        }
    }

    public function safeDown()
    {
        echo "m190627_100000_updateDraftId cannot be reverted.\n";

        return false;
    }
}
