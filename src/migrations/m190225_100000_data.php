<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;

class m190225_100000_data extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'data')) {
            $this->addColumn('{{%workflow_submissions}}', 'data', $this->mediumText()->after('publisherNotes'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190225_100000_data cannot be reverted.\n";
        return false;
    }
}
