<?php
namespace verbb\workflow\events;

use craft\models\UserGroup;
use yii\base\Event;

class ReviewerUserGroupsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var UserGroup[]
     */
    public $userGroups;
}
