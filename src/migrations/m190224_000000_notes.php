<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Plugins;

class m190224_000000_notes extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        if ($this->db->columnExists('{{%workflow_submissions}}', 'notes')) {
            MigrationHelper::renameColumn('{{%workflow_submissions}}', 'notes', 'publisherNotes', $this);
        }

        if (!$this->db->columnExists('{{%workflow_submissions}}', 'editorNotes')) {
            $this->addColumn('{{%workflow_submissions}}', 'editorNotes', $this->text()->after('status'));
        }
    }

    public function safeDown()
    {
        echo "m190224_000000_notes cannot be reverted.\n";
        return false;
    }
}
