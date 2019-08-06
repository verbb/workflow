<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

class m190806_000000_removeOwnerForeignKey extends Migration
{
    public function safeUp()
    {
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false));

        $this->alterColumn('{{%workflow_submissions}}', 'ownerId', $this->integer());

        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'SET NULL', null);
        
        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true));
    }

    public function safeDown()
    {
        echo "m190806_000000_removeOwnerForeignKey cannot be reverted.\n";

        return false;
    }
}
