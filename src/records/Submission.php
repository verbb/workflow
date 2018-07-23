<?php
namespace verbb\workflow\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Entry;
use craft\records\User;

use yii\db\ActiveQueryInterface;

class Submission extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%workflow_submissions}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(Entry::class, ['id' => 'ownerId']);
    }

    public function getEditor(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'editorId']);
    }

    public function getPublisher(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'publisherId']);
    }
}

