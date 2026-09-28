<?php
namespace verbb\workflow\elements\actions;

use verbb\workflow\Workflow;
use verbb\workflow\models\Review;

use Craft;
use craft\elements\actions\SetStatus as BaseSetStatus;
use craft\elements\db\ElementQueryInterface;

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

    public function getTriggerHtml(): ?string
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        return Craft::$app->getView()->renderTemplate('workflow/_elementactions/status', [
            'statuses' => Workflow::$plugin->getSubmissionPermissions()->getAllowedBulkStatuses($currentUser, $currentSite),
        ]);
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

            if (!Workflow::$plugin->getSubmissionPermissions()->canChangeStatus($currentUser, $submission, $this->status)) {
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
        $rules[] = [['status'], 'in', 'range' => [
            Review::STATUS_APPROVED,
            Review::STATUS_REJECTED,
            Review::STATUS_REVOKED,
        ]];

        return $rules;
    }
}
