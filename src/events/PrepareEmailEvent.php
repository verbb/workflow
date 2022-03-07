<?php
namespace verbb\workflow\events;

use verbb\workflow\elements\Submission;

use craft\elements\User;
use craft\events\CancelableEvent;

class PrepareEmailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?User $editor = null;
    public ?array $publishers = null;
    public ?array $reviewers = null;

}
