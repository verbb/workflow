<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use Craft;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
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

        $settings = Workflow::$plugin->getSettings();
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($submission === null) {
            $submission = Submission::find()->id($submissionId)->siteId('*')->one();

            if (!$submission) {
                throw new NotFoundHttpException('Submission not found');
            }
        }

        $canEdit = true;
        $editorGroup = $settings->getEditorUserGroup($submission->site);

        if ($editorGroup && $currentUser && $currentUser->isInGroup($editorGroup)) {
            $canEdit = false;
        }

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

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $submissionId = (int)$request->getParam('submissionId');
        $siteId = (int)$request->getParam('siteId');
        $submission = Workflow::$plugin->getSubmissions()->getSubmissionById($submissionId, $siteId);
        $status = $request->getParam('status');

        if (!$submission) {
            $session->setError(Craft::t('workflow', 'Unable to find submission.'));

            return null;
        }

        // Skip if there's nothing to change
        if ($submission->status !== $status) {
            // If trying to approve their own submission, fail
            if ($status === Review::STATUS_APPROVED && $submission->editorId === $currentUser->id) {
                $session->setError(Craft::t('workflow', 'You cannot approve your own submission.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'submission' => $submission,
                    'errors' => $submission->getErrors(),
                ]);

                return null;
            } else {
                Workflow::$plugin->getSubmissions()->triggerSubmissionStatus($status, $submission);
            }
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

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submissionId = $request->getParam('submissionId');

        if (!Craft::$app->getElements()->deleteElementById($submissionId)) {
            $session->setError(Craft::t('workflow', 'Unable to delete submission.'));

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Submission deleted.'));

        return $this->redirectToPostedUrl();
    }
}
