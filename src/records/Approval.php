<?php
namespace verbb\workflow\records;

use craft\db\ActiveRecord;
use craft\records\User;

use yii\db\ActiveQueryInterface;

class Approval extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%workflow_submission_history}}';
    }

    public function getSubmission(): ActiveQueryInterface
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionId']);
    }

    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}

