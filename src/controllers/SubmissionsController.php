<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SubmissionsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionEdit(?Submission $submission, ?int $submissionId = null): Response
    {
        $this->requireCpRequest();
        $this->requirePermission('workflow-overview');

        $settings = Workflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($submission === null) {
            $submission = Submission::find()->id($submissionId)->siteId('*')->one();

            if (!$submission) {
                throw new NotFoundHttpException('Submission not found');
            }
        }

        if (!$currentUser || !Craft::$app->getElements()->canView($submission, $currentUser)) {
            throw new ForbiddenHttpException('You are not allowed to view this submission.');
        }

        $canEdit = Workflow::$plugin->getSubmissionPermissions()->canManageSubmission($currentUser, $submission);

        $variables = [
            'submission' => $submission,
            'title' => $submission->title,
            'settings' => $settings,
            'canEdit' => $canEdit,
        ];

        $variables['changesCount'] = Workflow::$plugin->getContent()->getContentChangesTotalCount($submission);

        return $this->renderTemplate('workflow/submissions/_edit', $variables);
    }

    public function actionSaveSubmission(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('workflow-overview');

        $session = Craft::$app->getSession();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $submissionId = (int)$this->request->getParam('submissionId');
        $siteId = (int)$this->request->getParam('siteId');
        $submission = Workflow::$plugin->getSubmissions()->getSubmissionById($submissionId, $siteId);
        $status = (string)$this->request->getRequiredBodyParam('status');

        if (!$submission) {
            $session->setError(Craft::t('workflow', 'Unable to find submission.'));

            return null;
        }

        if (!$currentUser || !Workflow::$plugin->getSubmissionPermissions()->canChangeStatus($currentUser, $submission, $status)) {
            throw new ForbiddenHttpException('You are not allowed to change this submission status.');
        }

        if ($submission->status === $status) {
            return $this->redirectToPostedUrl($submission);
        }

        if (!Workflow::$plugin->getSubmissions()->triggerSubmissionStatus($status, $submission)) {
            $session->setError(Craft::t('workflow', 'Unable to change submission status.'));

            return null;
        }

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Unable to save submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'errors' => $submission->getErrors(),
            ]);

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Submission saved successfully.'));

        return $this->redirectToPostedUrl($submission);
    }

    public function actionDeleteSubmission(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('workflow-overview');

        $session = Craft::$app->getSession();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $submissionId = (int)$this->request->getRequiredBodyParam('submissionId');
        $submission = Submission::find()
            ->id($submissionId)
            ->siteId('*')
            ->status(null)
            ->one();

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found');
        }

        if (!$currentUser || !Craft::$app->getElements()->canDelete($submission, $currentUser)) {
            throw new ForbiddenHttpException('You are not allowed to delete this submission.');
        }

        if (!Craft::$app->getElements()->deleteElement($submission)) {
            $session->setError(Craft::t('workflow', 'Unable to delete submission.'));

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Submission deleted.'));

        return $this->redirectToPostedUrl();
    }
}
