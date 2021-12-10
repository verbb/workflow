<?php
namespace verbb\workflow\events;

use craft\events\CancelableEvent;

class PrepareEmailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $editor;
    public $publishers;
    public $reviewers;

}
