<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m190806_000000_removeOwnerForeignKey extends Migration
{
    public function safeUp(): bool
    {
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false, '', '{{%workflow_submissions}}'));

        $this->alterColumn('{{%workflow_submissions}}', 'ownerId', $this->integer());

        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'SET NULL', null);

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true, '', '{{%workflow_submissions}}'));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190806_000000_removeOwnerForeignKey cannot be reverted.\n";

        return false;
    }
}
