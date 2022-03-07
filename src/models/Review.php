<?php
namespace verbb\workflow\models;

use verbb\workflow\records\Review as ReviewRecord;

use craft\base\Model;

use DateTime;

class Review extends Model
{
    // Static Methods
    // =========================================================================

    /**
     * Populates a new model instance with a given set of attributes.
     *
     * @param mixed $values
     * @return Review
     */
    public static function populateModel(mixed $values): Review
    {
        if ($values instanceof Model || $values instanceof ReviewRecord) {
            $values = $values->getAttributes();
        }

        $review = new Review();
        $review->setAttributes($values, false);

        return $review;
    }
    

    // Properties
    // =========================================================================

    public ?int $submissionId = null;
    public ?int $userId = null;
    public bool $approved = true;
    public ?string $notes = null;
    public ?DateTime $dateCreated = null;

}
