<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

class m190713_000000_fixDraftForeignKey extends Migration
{
    public function safeUp()
    {
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false, '', '{{%workflow_submissions}}'));

        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerDraftId'], $this);

        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerDraftId', '{{%drafts}}', 'id', 'SET NULL', null);
        
        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true, '', '{{%workflow_submissions}}'));
    }

    public function safeDown()
    {
        echo "m190713_000000_fixDraftForeignKey cannot be reverted.\n";

        return false;
    }
}
