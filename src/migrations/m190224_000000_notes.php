<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\Db;

class m190224_000000_notes extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if ($this->db->columnExists('{{%workflow_submissions}}', 'notes')) {
            Db::renameColumn('{{%workflow_submissions}}', 'notes', 'publisherNotes', $this);
        }

        if (!$this->db->columnExists('{{%workflow_submissions}}', 'editorNotes')) {
            $this->addColumn('{{%workflow_submissions}}', 'editorNotes', $this->text()->after('status'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190224_000000_notes cannot be reverted.\n";
        return false;
    }
}
