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
}
