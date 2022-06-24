<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Plugins;

class m190225_000000_update_draft_fk extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['draftId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'draftId', '{{%entrydrafts}}', 'id', 'SET NULL', null);
    }

    public function safeDown()
    {
        echo "m190225_000000_update_draft_fk cannot be reverted.\n";
        return false;
    }
}
