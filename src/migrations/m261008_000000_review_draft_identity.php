<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261008_000000_review_draft_identity extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Keep the submitted target even if Craft later clears the draft foreign key.
        $reviews = (new Query())
            ->select(['id', 'draftId', 'data'])
            ->from('{{%workflow_reviews}}')
            ->where(['not', ['draftId' => null]]);

        foreach ($reviews->batch() as $batch) {
            foreach ($batch as $review) {
                $data = is_array($review['data']) ? $review['data'] : Json::decode($review['data'] ?? '[]');
                $data['draftId'] = (int)$review['draftId'];
                $this->update('{{%workflow_reviews}}', ['data' => Json::encode($data)], ['id' => $review['id']]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261008_000000_review_draft_identity cannot be reverted.\n";
        return false;
    }
}
