<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Plugins;

class m190225_100000_data extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'data')) {
            $this->addColumn('{{%workflow_submissions}}', 'data', $this->mediumText()->after('publisherNotes'));
        }
    }

    public function safeDown()
    {
        echo "m190225_100000_data cannot be reverted.\n";
        return false;
    }
}
