<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;

class m221103_000000_canonical extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'ownerCanonicalId')) {
            $this->addColumn('{{%workflow_submissions}}', 'ownerCanonicalId', $this->integer()->after('ownerDraftId'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221103_000000_canonical cannot be reverted.\n";
        return false;
    }
}
