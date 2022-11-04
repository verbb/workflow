<?php
namespace verbb\workflow\models;

use verbb\workflow\helpers\StringHelper;
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
    public ?DateTime $dateCreated = null;

    private ?string $_notes = null;


    // Public Methods
    // =========================================================================

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'notes';

        return $attributes;
    }

    public function setNotes($value): void
    {
        $this->_notes = StringHelper::sanitizeNotes($value);
    }

    public function getNotes(bool $sanitize = true): ?string
    {
        return $sanitize ? StringHelper::unSanitizeNotes($this->_notes) : $this->_notes;
    }

    public function getConfig(): array
    {
        return [
            'submissionId' => $this->submissionId,
            'userId' => $this->userId,
            'approved' => $this->approved,
            'dateCreated' => $this->dateCreated,
            'notes' => $this->getNotes(false),
        ];
    }

}
