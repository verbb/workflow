<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;

class SubmissionsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSaveSubmission($entry = null, $draft = null)
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
        $submission->editorNotes = $request->getParam('editorNotes', $submission->editorNotes);
        $submission->publisherNotes = $request->getParam('publisherNotes', $submission->publisherNotes);

        if ($entry) {
            $submission->ownerId = $entry->id;
            $submission->ownerSiteId = $entry->siteId;
            $submission->data = $this->_getRevisionData($entry);
        }

        if ($draft) {
            $submission->draftId = $draft->draftId;
            $submission->data = $this->_getRevisionData($draft);
        }

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

        return $this->redirectToPostedUrl();
    }

    public function actionRevokeSubmission($entry = null, $draft = null)
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

        return $this->redirectToPostedUrl();
    }

    public function actionApproveSubmission($entry = null, $draft = null)
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_APPROVED;
        $submission->publisherId = $currentUser->id;
        $submission->dateApproved = new \DateTime;
        $submission->editorNotes = $request->getParam('editorNotes', $submission->editorNotes);
        $submission->publisherNotes = $request->getParam('publisherNotes', $submission->publisherNotes);

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not approve and publish.'));
            return null;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getSubmissions()->sendEditorNotificationEmail($submission);
        }

        $session->setNotice(Craft::t('workflow', 'Entry approved and published.'));

        return $this->redirectToPostedUrl();
    }

    public function actionRejectSubmission($entry = null, $draft = null)
    {
        $settings = Workflow::$plugin->getSettings();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submission = $this->_setSubmissionFromPost();
        $submission->status = Submission::STATUS_REJECTED;
        $submission->publisherId = $currentUser->id;
        $submission->dateRejected = new \DateTime;
        $submission->editorNotes = $request->getParam('editorNotes', $submission->editorNotes);
        $submission->publisherNotes = $request->getParam('publisherNotes', $submission->publisherNotes);

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Could not reject submission.'));
            return null;
        }

        // Trigger notification to editor
        if ($settings->editorNotifications) {
            Workflow::$plugin->getSubmissions()->sendEditorNotificationEmail($submission);
        }

        return $this->redirectToPostedUrl();
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

    private function _getRevisionData(Entry $revision): array
    {
        $revisionData = [
            'typeId' => $revision->typeId,
            'authorId' => $revision->authorId,
            'title' => $revision->title,
            'slug' => $revision->slug,
            'postDate' => $revision->postDate ? $revision->postDate->getTimestamp() : null,
            'expiryDate' => $revision->expiryDate ? $revision->expiryDate->getTimestamp() : null,
            'enabled' => $revision->enabled,
            'newParentId' => $revision->newParentId,
            'fields' => [],
        ];

        $content = $revision->getSerializedFieldValues();

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if (isset($content[$field->handle]) && $content[$field->handle] !== null) {
                $revisionData['fields'][$field->id] = $content[$field->handle];
            }
        }

        return $revisionData;
    }

}
