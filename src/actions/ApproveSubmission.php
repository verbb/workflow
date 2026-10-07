<?php
namespace verbb\workflow\actions;

use verbb\workflow\Workflow;
use verbb\workflow\base\Action;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\helpers\DateTimeHelper;

use DateTime;

class ApproveSubmission extends Action
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'approve-submission';
    }

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Approve and Publish');
    }


    // Public Methods
    // =========================================================================

    public function onBeforeSaveEntry(ModelEvent $event): void
    {
        $settings = Workflow::$plugin->getSettings();
        $request = Craft::$app->getRequest();
        $currentSite = $event->sender->site;

        $workflowNotes = (string)$request->getBodyParam('workflowNotes');

        // Automatically set the entry "live", saving users from having to enable and set a post date manually.
        $event->sender->enabled = true;
        $event->sender->enabledForSite = true;
        $event->sender->setScenario(Element::SCENARIO_LIVE);

        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $event->sender->postDate = DateTimeHelper::toDateTime($postDate) ?: new DateTime();
        }

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
