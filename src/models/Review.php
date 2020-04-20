<?php
namespace verbb\workflow\models;

use craft\base\Model;

class Review extends Model
{
    // Public Properties
    // =========================================================================

    public $submissionId;
    public $userId;
    public $approved = true;
    public $notes = '';
    public $dateCreated = '';

    // Static Methods
    // =========================================================================

    /**
     * Populates a new model instance with a given set of attributes.
     *
     * @param mixed $values
     * @return Review
     */
    public static function populateModel($values): Review
    {
        if ($values instanceof Model) {
            $values = $values->getAttributes();
        }

        $review = new Review();
        $review->setAttributes($values, false);

        return $review;
    }
}
