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

    public function actionCompare(?int $newReviewId = null, ?int $oldReviewId = null): Response
    {
        $this->requireCpRequest();

        $reviewsService = Workflow::$plugin->getReviews();

        $newReview = $reviewsService->getReviewById($newReviewId);
        $oldReview = $reviewsService->getReviewById($oldReviewId);

        if (!$newReview || !$oldReview) {
            throw new NotFoundHttpException('Review not found');
        }

        $variables = [
            'newReview' => $newReview,
            'oldReview' => $oldReview,
            'diff' => Workflow::$plugin->getContent()->getDiff($oldReview->data, $newReview->data),
            'title' => "Compare review #{$oldReview->id} to #{$newReview->id}",
        ];

        return $this->renderTemplate('workflow/reviews/_compare', $variables);
    }

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

    public function actionGetCompareModalBody(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $view = $this->getView();
        $reviewsService = Workflow::$plugin->getReviews();

        $reviewId = $request->getParam('reviewId');
        $newReview = $reviewsService->getReviewById($reviewId);

        // Get the previous review
        $oldReview = $reviewsService->getPreviousReviewById($reviewId);

        $view->registerAssetBundle(\verbb\workflow\assetbundles\WorkflowAsset::class);

        $html = $view->renderTemplate('workflow/reviews/_compare-modal', [
            'review' => $newReview,
            'diff' => Workflow::$plugin->getContent()->getDiff($oldReview->data, $newReview->data),
        ]);

        $headHtml = $view->getHeadHtml();
        $footHtml = $view->getBodyHtml();

        return $this->asJson([
            'success' => true,
            'html' => $html,
            'headHtml' => $headHtml,
            'footHtml' => $footHtml,
        ]);
    }
}
