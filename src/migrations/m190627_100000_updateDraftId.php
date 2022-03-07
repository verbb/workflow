<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m190627_100000_updateDraftId extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'ownerDraftId')) {
            MigrationHelper::renameColumn('{{%workflow_submissions}}', 'draftId', 'ownerDraftId', $this);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190627_100000_updateDraftId cannot be reverted.\n";

        return false;
    }
}
