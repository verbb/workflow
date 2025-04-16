<?php
namespace verbb\workflow\actions;

use verbb\workflow\Workflow;
use verbb\workflow\base\Action;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use Craft;
use craft\base\ElementInterface;
use craft\events\ElementEvent;
use craft\events\ModelEvent;

class RejectReview extends Action
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'reject-review';
    }

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Reject');
    }


    // Public Methods
    // =========================================================================

    public function onAfterSaveElement(ElementEvent $event): void
    {
        Workflow::$plugin->getSubmissions()->rejectReview($event->element);
    }


    // Protected Methods
    // =========================================================================

    protected function defineMenuItem(ElementInterface $element, Submission $submission, Review $review): array
    {
        return [
            'action' => $element->getIsUnpublishedDraft() ? 'elements/save-draft' : null,
            'redirect' => $element->getIsUnpublishedDraft() ? '{cpEditUrl}' : null,
        ];
    }
}