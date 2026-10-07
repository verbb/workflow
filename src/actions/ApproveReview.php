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

class ApproveReview extends Action
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'approve-review';
    }

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Approve');
    }


    // Public Methods
    // =========================================================================

    public function onAfterSaveElement(ElementEvent $event): void
    {
        $this->requireTransition(Workflow::$plugin->getSubmissions()->approveReview($event->element), $event->element);
    }


    // Protected Methods
    // =========================================================================

    protected function defineMenuItem(ElementInterface $element, Submission $submission, Review $review): array
    {
        return [
            'action' => $element->getIsDraft() ? 'elements/save-draft' : 'elements/save',
            'redirect' => '{cpEditUrl}',
        ];
    }
}
