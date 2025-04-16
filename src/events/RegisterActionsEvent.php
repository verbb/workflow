<?php
namespace verbb\workflow\events;

use craft\events\CancelableEvent;

class RegisterActionsEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public array $actions = [];

}
