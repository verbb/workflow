<?php
namespace verbb\workflow\records;

use craft\db\ActiveRecord;
use craft\records\User;

use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property int $submissionId
 * @property int $userId
 * @property bool $approved
 * @property string $notes
 * @property DateTime $dateCreated
 **/
class Review extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%workflow_reviews}}';
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

