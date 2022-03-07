<?php
namespace verbb\workflow\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Entry;
use craft\records\User;

use craft\db\ActiveQuery;

class Submission extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%workflow_submissions}}';
    }

    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getOwner(): ActiveQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'ownerId']);
    }

    public function getEditor(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'editorId']);
    }

    public function getPublisher(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'publisherId']);
    }
}

