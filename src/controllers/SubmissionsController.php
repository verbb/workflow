<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

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

        if ($submission === null) {
            $submission = Submission::find()->id($submissionId)->one();

            if (!$submission) {
                throw new NotFoundHttpException('Submission not found');
            }
        }

        $variables = [
            'submission' => $submission,
            'title' => $submission->title,
        ];

        return $this->renderTemplate('workflow/submissions/_edit', $variables);
    }

    public function actionSaveSubmission(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $submissionId = $request->getParam('submissionId');
        $submission = Craft::$app->getElements()->getElementById($submissionId);

        if (!Craft::$app->getElements()->saveElement($submission)) {
            $session->setError(Craft::t('workflow', 'Unable to save submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
                'errors' => $submission->getErrors(),
            ]);

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Comment saved successfully.'));

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
