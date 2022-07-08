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
            'ownerDraftId' => $this->integer(),
            'editorId' => $this->integer(),
            'publisherId' => $this->integer(),
            'status' => $this->enum('status', ['approved', 'pending', 'rejected', 'revoked']),
            'editorNotes' => $this->text(),
            'publisherNotes' => $this->text(),
            'data' => $this->mediumText(),
            'dateApproved' => $this->dateTime(),
            'dateRejected' => $this->dateTime(),
            'dateRevoked' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%workflow_reviews}}');
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
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%workflow_submissions}}', 'id', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'ownerId', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'ownerDraftId', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'ownerSiteId', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'editorId', false);
        $this->createIndex(null, '{{%workflow_submissions}}', 'publisherId', false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerDraftId', '{{%drafts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerSiteId', '{{%sites}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'editorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_submissions}}', 'publisherId', '{{%users}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%workflow_reviews}}', 'submissionId', '{{%workflow_submissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
    }

    public function dropTables(): void
    {
        if ($this->db->tableExists('{{%workflow_submissions}}')) {
            $this->dropTable('{{%workflow_submissions}}');
        }

        if ($this->db->tableExists('{{%workflow_reviews}}')) {
            $this->dropTable('{{%workflow_reviews}}');
        }
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
