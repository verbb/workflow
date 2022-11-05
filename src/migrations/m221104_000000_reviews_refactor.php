<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\db\Query;

class m221104_000000_reviews_refactor extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Firstly, migrate all reviews to change the boolean `approved` to `status=approved`.
        if (!$this->db->columnExists('{{%workflow_reviews}}', 'elementId')) {
            $this->addColumn('{{%workflow_reviews}}', 'elementId', $this->integer()->after('submissionId'));
        }

        if (!$this->db->columnExists('{{%workflow_reviews}}', 'draftId')) {
            $this->addColumn('{{%workflow_reviews}}', 'draftId', $this->integer()->after('elementId'));
        }

        if (!$this->db->columnExists('{{%workflow_reviews}}', 'role')) {
            $this->addColumn('{{%workflow_reviews}}', 'role', $this->enum('role', ['editor', 'reviewer', 'publisher'])->after('userId'));
        }

        if (!$this->db->columnExists('{{%workflow_reviews}}', 'status')) {
            $this->addColumn('{{%workflow_reviews}}', 'status', $this->enum('status', ['approved', 'pending', 'rejected', 'revoked'])->after('role'));
        }

        if (!$this->db->columnExists('{{%workflow_reviews}}', 'data')) {
            $this->addColumn('{{%workflow_reviews}}', 'data', $this->mediumText()->after('notes'));
        }

        $this->createIndex(null, '{{%workflow_reviews}}', 'elementId', false);
        $this->createIndex(null, '{{%workflow_reviews}}', 'draftId', false);
        $this->createIndex(null, '{{%workflow_reviews}}', 'userId', false);

        $this->addForeignKey(null, '{{%workflow_reviews}}', 'elementId', '{{%elements}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'draftId', '{{%drafts}}', 'id', 'SET NULL', null);

        $reviews = (new Query())
            ->select(['*'])
            ->from(['{{%workflow_reviews}}'])
            ->all();

        foreach ($reviews as $review) {
            if (array_key_exists('approved', $review)) {
                $this->update('{{%workflow_reviews}}', [
                    'role' => 'reviewer',
                    'status' => ($review['approved'] ? 'approved' : null),
                ], ['id' => $review['id']]);
            }
        }

        // Then, remove the `approved` column as we no longer need it
        if ($this->db->columnExists('{{%workflow_reviews}}', 'approved')) {
            $this->dropColumn('{{%workflow_reviews}}', 'approved');
        }

        // Next we get all the submissions right now, and convert them to reviews. They'll only have a single review
        // because until now, we haven't captured enough data for the entire process, just modifying a single submission.
        $submissions = (new Query())
            ->select(['*'])
            ->from(['{{%workflow_submissions}}'])
            ->all();

        foreach ($submissions as $submission) {
            $review = [
                'submissionId' => $submission['id'],
                'elementId' => $submission['ownerId'],
                'draftId' => $submission['ownerDraftId'],
                'status' => $submission['status'],
                'data' => $submission['data'],
                'dateUpdated' => $submission['dateUpdated'],
            ];

            if ($submission['editorId']) {
                $review['role'] = 'editor';
                $review['userId'] = $submission['editorId'];
                $review['notes'] = $submission['editorNotes'];
            }

            if ($submission['publisherId']) {
                $review['role'] = 'publisher';
                $review['userId'] = $submission['publisherId'];
                $review['notes'] = $submission['publisherNotes'];
            }

            if ($submission['dateApproved']) {
                $review['dateCreated'] = $submission['dateApproved'];
            }

            if ($submission['dateRejected']) {
                $review['dateCreated'] = $submission['dateRejected'];
            }

            if ($submission['dateRevoked']) {
                $review['dateCreated'] = $submission['dateRevoked'];
            }

            $this->insert('{{%workflow_reviews}}', $review);
        }

        // Add the new column to track completion status
        if (!$this->db->columnExists('{{%workflow_submissions}}', 'isComplete')) {
            $this->addColumn('{{%workflow_submissions}}', 'isComplete', $this->boolean()->defaultValue(false)->after('ownerSiteId'));
        }

        // Update the submission before we drop tables, if `approved` or `revoked` assume it's completed
        foreach ($submissions as $submission) {
            if (array_key_exists('status', $submission)) {
                $this->update('{{%workflow_submissions}}', [
                    'isComplete' => ($submission['status'] === 'approved' || $submission['status'] === 'revoked' ? true : false),
                ], ['id' => $submission['id']]);
            }
        }

        // Then, make the schema changes to submissions
        $this->dropForeignKeyIfExists('{{%workflow_submissions}}', 'ownerDraftId');
        $this->dropForeignKeyIfExists('{{%workflow_submissions}}', 'editorId');
        $this->dropForeignKeyIfExists('{{%workflow_submissions}}', 'publisherId');
        $this->dropIndexIfExists('{{%workflow_submissions}}', 'ownerDraftId', false);
        $this->dropIndexIfExists('{{%workflow_submissions}}', 'ownerCanonicalId', false);
        $this->dropIndexIfExists('{{%workflow_submissions}}', 'editorId', false);
        $this->dropIndexIfExists('{{%workflow_submissions}}', 'publisherId', false);
        $this->dropIndexIfExists('{{%workflow_submissions}}', 'publisherId', false);

        if ($this->db->columnExists('{{%workflow_submissions}}', 'ownerDraftId')) {
            $this->dropColumn('{{%workflow_submissions}}', 'ownerDraftId');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'ownerCanonicalId')) {
            $this->dropColumn('{{%workflow_submissions}}', 'ownerCanonicalId');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'editorId')) {
            $this->dropColumn('{{%workflow_submissions}}', 'editorId');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'publisherId')) {
            $this->dropColumn('{{%workflow_submissions}}', 'publisherId');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'status')) {
            $this->dropColumn('{{%workflow_submissions}}', 'status');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'editorNotes')) {
            $this->dropColumn('{{%workflow_submissions}}', 'editorNotes');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'publisherNotes')) {
            $this->dropColumn('{{%workflow_submissions}}', 'publisherNotes');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'data')) {
            $this->dropColumn('{{%workflow_submissions}}', 'data');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'dateApproved')) {
            $this->dropColumn('{{%workflow_submissions}}', 'dateApproved');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'dateRejected')) {
            $this->dropColumn('{{%workflow_submissions}}', 'dateRejected');
        }

        if ($this->db->columnExists('{{%workflow_submissions}}', 'dateRevoked')) {
            $this->dropColumn('{{%workflow_submissions}}', 'dateRevoked');
        }

        if (!$this->db->columnExists('{{%workflow_submissions}}', 'isPending')) {
            $this->addColumn('{{%workflow_submissions}}', 'isPending', $this->boolean()->defaultValue(false)->after('isComplete'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221104_000000_reviews_refactor cannot be reverted.\n";
        return false;
    }
}
