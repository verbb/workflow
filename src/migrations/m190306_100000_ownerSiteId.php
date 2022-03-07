<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;

class m190306_100000_ownerSiteId extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'ownerSiteId')) {
            $this->addColumn('{{%workflow_submissions}}', 'ownerSiteId', $this->integer()->after('ownerId'));

            $this->createIndex(null, '{{%workflow_submissions}}', 'ownerSiteId', false);
            $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerSiteId', '{{%sites}}', 'id', 'CASCADE', null);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190306_100000_ownerSiteId cannot be reverted.\n";

        return false;
    }
}
