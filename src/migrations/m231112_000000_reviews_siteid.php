<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\db\Query;

use Throwable;

class m231112_000000_reviews_siteid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%workflow_reviews}}', 'elementSiteId')) {
            $this->addColumn('{{%workflow_reviews}}', 'elementSiteId', $this->integer()->after('elementId'));
        }

        $this->createIndex(null, '{{%workflow_reviews}}', 'elementSiteId', false);
        $this->addForeignKey(null, '{{%workflow_reviews}}', 'elementSiteId', '{{%sites}}', 'id', 'CASCADE', null);

        // Populate the site info from submissions
        $submissions = (new Query())
            ->select(['id', 'ownerSiteId'])
            ->from(['{{%workflow_submissions}}'])
            ->indexBy('id')
            ->all();

        $reviews = (new Query())
            ->select(['*'])
            ->from(['{{%workflow_reviews}}'])
            ->all();

        foreach ($reviews as $review) {
            $submissionId = $review['submissionId'] ?? null;

            if ($submissionId) {
                $this->update('{{%workflow_reviews}}', [
                    'elementSiteId' => $submissions[$submissionId]['ownerSiteId'] ?? null,
                ], ['id' => $review['id']]);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231112_000000_reviews_siteid cannot be reverted.\n";
        return false;
    }
}
