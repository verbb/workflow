<?php
namespace verbb\workflow\events;

use verbb\workflow\elements\Submission;

use craft\elements\User;
use craft\models\Site;

use yii\base\Event;

class DefinePublisherSelfApprovalEvent extends Event
{
    // Properties
    // =========================================================================

    public User $user;
    public Submission $submission;
    public Site $site;
    public bool $allowSelfApproval = false;
}
