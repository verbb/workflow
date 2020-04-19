<?php
namespace verbb\workflow\models;

use craft\base\Model;

class Approval extends Model
{
    // Public Properties
    // =========================================================================

    public $submissionId;
    public $editorId;
    public $approved = true;
    public $notes = '';
}
