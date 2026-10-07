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

class ApproveApplySubmission extends Action
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'approve-apply-submission';
    }

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Approve and Apply');
    }


    // Public Methods
    // =========================================================================

    public function onBeforeSaveEntry(ModelEvent $event): void
    {
        $settings = Workflow::$plugin->getSettings();
        $request = Craft::$app->getRequest();
        $currentSite = $event->sender->site;

        $workflowNotes = (string)$request->getBodyParam('workflowNotes');

        // We also need to validate notes fields, if required before we save the entry
        if ($settings->getPublisherNotesRequired($currentSite) && !$workflowNotes) {
            Craft::$app->getUrlManager()->setRouteParams([
                'workflowNotesErrors' => [Craft::t('workflow', 'Notes are required')],
            ]);

            $event->isValid = false;
        }
    }

    public function onAfterSaveElement(ElementEvent $event): void
    {
        // Draft approvals are completed by the application hooks after Craft saves the canonical entry.
        if (!$event->element->getIsDraft() && !$event->element->getIsRevision()) {
            $this->requireTransition(Workflow::$plugin->getSubmissions()->approveSubmission($event->element), $event->element);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineMenuItem(ElementInterface $element, Submission $submission, Review $review): array
    {
        return [
            'action' => $element->getIsDraft() ? 'elements/apply-draft' : 'elements/save',
            'redirect' => '{cpEditUrl}',
        ];
    }
}
