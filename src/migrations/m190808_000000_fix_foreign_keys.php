<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\Db;

class m190808_000000_fix_foreign_keys extends Migration
{
    public function safeUp(): bool
    {
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false, '', '{{%workflow_submissions}}'));

        Db::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerId'], $this);
        Db::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerDraftId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerDraftId', '{{%drafts}}', 'id', 'SET NULL', null);

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true, '', '{{%workflow_submissions}}'));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190808_000000_fix_foreign_keys cannot be reverted.\n";

        return false;
    }
}
