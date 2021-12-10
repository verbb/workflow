<?php
namespace verbb\workflow\events;

use craft\events\CancelableEvent;

class EmailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $mail;
    public $user;
    public $submission;

}
