<?php
namespace verbb\workflow\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use craft\db\ActiveQuery;

class Submission extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%workflow_submissions}}';
    }

    public function getOwner(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'ownerId']);
    }
}

