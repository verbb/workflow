<?php
namespace verbb\workflow\events;

use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use craft\elements\User;
use craft\events\CancelableEvent;

class EmailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public mixed $mail = null;
    public ?User $user = null;
    public ?Submission $submission = null;
    public ?Review $review = null;

}
