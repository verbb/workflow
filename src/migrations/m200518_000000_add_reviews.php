<?php
namespace verbb\workflow\migrations;

use Craft;
use craft\db\Migration;

class m200518_000000_add_reviews extends Migration
{
    public function safeUp(): bool
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

            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200518_000000_add_reviews cannot be reverted.\n";

        return false;
    }
}
