<?php
namespace verbb\workflow\events;

use verbb\workflow\models\Review;

use yii\base\Event;

class ReviewEvent extends Event
{
    // Properties
    // =========================================================================

    public Review $review;
    public bool $isNew = false;

}
