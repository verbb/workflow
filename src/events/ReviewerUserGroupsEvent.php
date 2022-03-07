<?php
namespace verbb\workflow\events;

use verbb\workflow\elements\Submission;
use yii\base\Event;

class ReviewerUserGroupsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?array $userGroups = null;
}
