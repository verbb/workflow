<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

class m200518_000000_add_reviews extends Migration
{
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%workflow_reviews}}')) {
            $this->createTable('{{%workflow_reviews}}', [
                'id' => $this->primaryKey(),
                'submissionId' => $this->integer(),
                'userId' => $this->integer(),
                'approved' => $this->boolean(),
                'notes' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->addForeignKey(null, '{{%workflow_reviews}}', 'submissionId', '{{%workflow_submissions}}', 'id', 'CASCADE', null);
            $this->addForeignKey(null, '{{%workflow_reviews}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);

            Craft::$app->getDb()->schema->refresh();
        }
    }

    public function safeDown()
    {
        echo "m200518_000000_add_reviews cannot be reverted.\n";

        return false;
    }
}
