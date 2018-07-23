<?php
namespace verbb\workflow\controllers;

use verbb\workflow\elements\Submission;

use Craft;
use craft\web\Controller;

use verbb\workflow\Workflow;

class SubmissionsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSend()
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->ownerId = $request->getParam('entryId');
        $submission->ownerSiteId = $request->getParam('siteId', Craft::$app->getSites()->getCurrentSite()->id);
        $submission->draftId = $request->getParam('draftId');
        $submission->editorId = $currentUser->id;
        $submission->status = Submission::STATUS_PENDING;
        $submission->dateApproved = null;

        $isNew = !$submission->id;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not submit for approval.'));
            return null;
        }

        if ($isNew) {
            // Trigger notification to publisher
            if ($settings->publisherNotifications) {
                Workflow::$plugin->getSubmissions()->sendPublisherNotificationEmail($submission);
            }
        }

        $session->setNotice(Craft::t('workflow', 'Entry submitted for approval.'));

        // Redirect page to the entry as its not a form submission
        return $this->redirect($request->referrer);
    }

    public function actionRevoke()
    {
        $settings = Workflow::$plugin->getSettings();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REVOKED;
        $submission->dateRevoked = new \DateTime;

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not revoke submission.'));
            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Submission revoked.'));

        // Redirect page to the entry as its not a form submission
        return $this->redirect($request->referrer);
    }

    public function actionApprove()
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_APPROVED;
        $submission->publisherId = $currentUser->id;
        $submission->dateApproved = new \DateTime;
        $submission->notes = $request->getParam('notes');

        $draftId = $request->getParam('draftId');

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve and publish.'));
            return null;
        }

        // Check if we're approving a draft - we publish it too.
        if ($draftId) {
            $draft = Craft::$app->getEntryRevisions()->getDraftById($draftId);
            
            if (!Craft::$app->getEntryRevisions()->publishDraft($draft)) {
                Craft::$app->getSession()->setError(Craft::t('workflow', 'Couldn’t publish draft.'));
                return null;
            }
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getSubmissions()->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('workflow', 'Entry approved and published.'));

        // Redirect page to the entry as its not a form submission - check for draft
        if ($draftId) {
            // If we've published a draft the url has changed
            return $this->redirect($draft->cpEditUrl);
        } else {
            return $this->redirect($request->referrer);
        }
    }

    public function actionReject()
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REJECTED;
        $submission->publisherId = $currentUser->id;
        $submission->dateRejected = new \DateTime;
        $submission->notes = $request->getParam('notes');

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not reject submission.'));
            return null;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getSubmissions()->sendEditorNotificationEmail($submission);
        }

        // Redirect page to the entry as its not a form submission
        return $this->redirect($request->referrer);
    }


    // Private Methods
    // =========================================================================

    private function _setSubmissionFromPost(): Submission
    {
        $request = Craft::$app->getRequest();
        $submissionId = $request->getParam('submissionId');

        if ($submissionId) {
            $submission = Workflow::$plugin->getSubmissions()->getSubmissionById($submissionId);

            if (!$submission) {
                throw new \Exception(Craft::t('workflow', 'No submission with the ID “{id}”', ['id' => $submissionId]));
            }
        } else {
            $submission = new Submission();
        }

        return $submission;
    }

}
