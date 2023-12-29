<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\helpers\Db;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%workflow_submissions}}');
        $this->createTable('{{%workflow_submissions}}', [
            'id' => $this->primaryKey(),
            'ownerId' => $this->integer(),
            'ownerSiteId' => $this->integer(),
            'isComplete' => $this->boolean()->defaultValue(false),
            'isPending' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%workflow_reviews}}');
        $this->createTable('{{%workflow_reviews}}', [
            'id' => $this->primaryKey(),
            'submissionId' => $this->integer(),
            'elementId' => $this->integer(),
            'elementSiteId' => $this->integer(),
            'draftId' => $this->integer(),
            'userId' => $this->integer(),
            'role' => $this->enum('role', ['editor', 'reviewer', 'publisher']),
            'status' => $this->enum('status', ['approved', 'pending', 'rejected', 'revoked']),
            'notes' => $this->text(),
            'data' => $this->mediumText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%workflow_submissions}}', 'id', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'ownerId', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'ownerSiteId', false);

        $this->createIndex(null, '{{%workflow_reviews}}', 'elementId', false);
        $this->createIndex(null, '{{%workflow_reviews}}', 'elementSiteId', false);
        $this->createIndex(null, '{{%workflow_reviews}}', 'draftId', false);
        $this->createIndex(null, '{{%workflow_reviews}}', 'userId', false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerSiteId', '{{%sites}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%workflow_reviews}}', 'submissionId', '{{%workflow_submissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'elementId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'elementSiteId', '{{%sites}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'draftId', '{{%drafts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%workflow_submissions}}');
        $this->dropTableIfExists('{{%workflow_reviews}}');
    }

    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%workflow_submissions}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%workflow_submissions}}', $this);
        }

        if ($this->db->tableExists('{{%workflow_reviews}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%workflow_reviews}}', $this);
        }
    }
}
