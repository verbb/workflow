<?php
namespace verbb\workflow\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    public function safeDown()
    {
        $this->dropForeignKeys();
        $this->dropTables();
    }

    public function createTables()
    {
        $this->createTable('{{%workflow_submissions}}', [
            'id' => $this->primaryKey(),
            'ownerId' => $this->integer()->notNull(),
            'draftId' => $this->integer(),
            'editorId' => $this->integer(),
            'publisherId' => $this->integer(),
            'status' => $this->enum('status', ['approved', 'pending', 'rejected', 'revoked']),
            'notes' => $this->text(),
            'dateApproved' => $this->dateTime(),
            'dateRejected' => $this->dateTime(),
            'dateRevoked' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }
    
    public function dropTables()
    {
        $this->dropTable('{{%workflow_submissions}}');
    }
    
    public function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%workflow_submissions}}', 'id', false), '{{%workflow_submissions}}', 'id', false);
        $this->createIndex($this->db->getIndexName('{{%workflow_submissions}}', 'ownerId', false), '{{%workflow_submissions}}', 'ownerId', false);
        $this->createIndex($this->db->getIndexName('{{%workflow_submissions}}', 'draftId', false), '{{%workflow_submissions}}', 'draftId', false);
        $this->createIndex($this->db->getIndexName('{{%workflow_submissions}}', 'editorId', false), '{{%workflow_submissions}}', 'editorId', false);
        $this->createIndex($this->db->getIndexName('{{%workflow_submissions}}', 'publisherId', false), '{{%workflow_submissions}}', 'publisherId', false);
    }

    public function addForeignKeys()
    {
        $this->addForeignKey($this->db->getForeignKeyName('{{%workflow_submissions}}', 'id'), '{{%workflow_submissions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%workflow_submissions}}', 'draftId'), '{{%workflow_submissions}}', 'draftId', '{{%entrydrafts}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%workflow_submissions}}', 'editorId'), '{{%workflow_submissions}}', 'editorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%workflow_submissions}}', 'ownerId'), '{{%workflow_submissions}}', 'ownerId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%workflow_submissions}}', 'publisherId'), '{{%workflow_submissions}}', 'publisherId', '{{%users}}', 'id', 'CASCADE', null);
    }
    
    public function dropForeignKeys()
    {
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['id'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['draftId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['editorId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['ownerId'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%workflow_submissions}}', ['publisherId'], $this);
    }
}
