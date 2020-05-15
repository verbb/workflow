<?php
namespace verbb\workflow\events;

use craft\models\UserGroup;
use verbb\workflow\elements\Submission;
use yii\base\Event;

class ReviewerUserGroupsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Submission|null
     */
    public $submission;

    /**
     * @var UserGroup[]
     */
    public $userGroups;
}
