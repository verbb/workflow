<?php
namespace verbb\workflow\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\User;

use craft\db\ActiveQuery;

class Review extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%workflow_reviews}}';
    }

    public function getSubmission(): ActiveQuery
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionId']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}

