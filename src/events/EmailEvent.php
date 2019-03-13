<?php
namespace verbb\workflow\events;

use yii\base\Event;

class EmailEvent extends Event
{
    // Properties
    // =========================================================================

    public $mail;
    public $user;

}
