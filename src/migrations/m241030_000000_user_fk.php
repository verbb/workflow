<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\db\Query;

use Throwable;

class m241030_000000_user_fk extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists('{{%workflow_reviews}}', 'submissionId');
        $this->dropForeignKeyIfExists('{{%workflow_reviews}}', 'elementId');
        $this->dropForeignKeyIfExists('{{%workflow_reviews}}', 'elementSiteId');
        $this->dropForeignKeyIfExists('{{%workflow_reviews}}', 'draftId');
        $this->dropForeignKeyIfExists('{{%workflow_reviews}}', 'userId');

        $this->addForeignKey(null, '{{%workflow_reviews}}', 'submissionId', '{{%workflow_submissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'elementId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'elementSiteId', '{{%sites}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'draftId', '{{%drafts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'userId', '{{%users}}', 'id', 'SET NULL', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m241030_000000_user_fk cannot be reverted.\n";
        return false;
    }
}
