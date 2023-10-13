<?php
namespace verbb\workflow\elements\actions;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\elements\Entry;
use craft\elements\actions\SetStatus as BaseSetStatus;
use craft\elements\db\ElementQueryInterface;

use DateTime;

class SetStatus extends BaseSetStatus
{
    // Properties
    // =========================================================================

    public ?string $status = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }


    // Public Methods
    // =========================================================================

    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('workflow/_elementactions/status');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $submissionsService = Workflow::$plugin->getSubmissions();

        $submissions = $query->all();
        $failCount = 0;

        $currentUser = Craft::$app->getUser()->getIdentity();

        foreach ($submissions as $submission) {
            // Skip if there's nothing to change
            if ($submission->status == $this->status) {
                continue;
            }

            // If trying to approve their own submission, fail
            if ($this->status === Review::STATUS_APPROVED && $submission->editorId === $currentUser->id) {
                $failCount++;

                continue;
            }

            if (!$submissionsService->triggerSubmissionStatus($this->status, $submission)) {
                $failCount++;

                continue;
            }

            // Update it to reflect it back in the table
            $submission->clearReviews();
        }

        // Did all of them fail?
        if ($failCount === count($submissions)) {
            if (count($submissions) === 1) {
                $this->setMessage(Craft::t('workflow', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('workflow', 'Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('workflow', 'Status updated, with some failures due to validation errors.'));
        } else if (count($submissions) === 1) {
            $this->setMessage(Craft::t('workflow', 'Status updated.'));
        } else {
            $this->setMessage(Craft::t('workflow', 'Statuses updated.'));
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        // Overwrite the parent rules
        $rules = [];

        $rules[] = [['status'], 'required'];
        $rules[] = [['status'], 'in', 'range' => array_keys(Submission::statuses())];

        return $rules;
    }
}
