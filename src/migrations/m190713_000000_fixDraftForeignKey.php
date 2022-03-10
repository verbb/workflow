<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\Db;

class m190713_000000_fixDraftForeignKey extends Migration
{
    public function safeUp(): bool
    {
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false, '', '{{%workflow_submissions}}'));

        Db::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerDraftId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerDraftId', '{{%drafts}}', 'id', 'SET NULL', null);

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true, '', '{{%workflow_submissions}}'));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190713_000000_fixDraftForeignKey cannot be reverted.\n";

        return false;
    }
}
