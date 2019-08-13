<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

class m190808_000000_fix_foreign_keys extends Migration
{
    public function safeUp()
    {
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false));

        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerDraftId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerDraftId', '{{%drafts}}', 'id', 'SET NULL', null);

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true));
    }

    public function safeDown()
    {
        echo "m190808_000000_fix_foreign_keys cannot be reverted.\n";

        return false;
    }
}
