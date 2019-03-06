<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m190306_100000_ownerSiteId extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'ownerSiteId')) {
            $this->addColumn('{{%workflow_submissions}}', 'ownerSiteId', $this->integer()->after('ownerId'));

            $this->createIndex(null, '{{%workflow_submissions}}', 'ownerSiteId', false);
            $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerSiteId', '{{%sites}}', 'id', 'CASCADE', null);
        }
    }

    public function safeDown()
    {
        echo "m190306_100000_ownerSiteId cannot be reverted.\n";

        return false;
    }
}
