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

class ReviewsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionDeleteReview(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $reviewId = $request->getParam('reviewId');

        if (!Workflow::$plugin->getReviews()->deleteReviewById($reviewId)) {
            $session->setError(Craft::t('workflow', 'Unable to delete review.'));

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Review deleted.'));

        return $this->redirectToPostedUrl();
    }
}
