<?php
namespace verbb\workflow\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\elements\Entry;
use craft\elements\db\ElementQueryInterface;

class SetStatus extends ElementAction
{
    // Properties
    // =========================================================================

    public $status;

    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    // Public Methods
    // =========================================================================

    public function getTriggerHtml()
    {
        return Craft::$app->getView()->renderTemplate('workflow/_elementactions/status');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        $submissions = $query->all();
        $failCount = 0;

        $currentUser = Craft::$app->getUser()->getIdentity();

        foreach ($submissions as $submission) {
            // Skip if there's nothing to change
            if ($submission->status == $this->status) {
                continue;
            }

            $submission->status = $this->status;

            // Check if approving
            if ($this->status === 'approved') {
                $ownerId = $submission->ownerId;
                $ownerSiteId = $submission->ownerSiteId;
                $ownerDraftId = $submission->ownerDraftId;

                // If trying to approve their own submission, fail
                if ($submission->editorId == $currentUser->id) {
                    $failCount++;

                    continue;
                }

                if ($ownerDraftId) {
                    $draft = Entry::find()->draftId($ownerDraftId)->siteId($ownerSiteId)->anyStatus()->one();

                    if ($draft) {
                        $draft->setScenario(Element::SCENARIO_LIVE);
                        $draft->enabled = true;

                        // Publish Draft
                        $newEntry = Craft::$app->getDrafts()->applyDraft($draft);

                        // Update the submission info now the draft is gone
                        $submission->ownerId = $newEntry->id;
                        $submission->ownerDraftId = null;
                    }
                }
            }

            if ($elementsService->saveElement($submission) === false) {
                // Validation error
                $failCount++;
            }
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
        } else {
            if (count($submissions) === 1) {
                $this->setMessage(Craft::t('workflow', 'Status updated.'));
            } else {
                $this->setMessage(Craft::t('workflow', 'Statuses updated.'));
            }
        }

        return true;
    }
}
