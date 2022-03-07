<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m190225_000000_update_draft_fk extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['draftId'], $this);
        
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'draftId', '{{%entrydrafts}}', 'id', 'SET NULL', null);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190225_000000_update_draft_fk cannot be reverted.\n";
        return false;
    }
}
