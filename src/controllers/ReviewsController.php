<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;

use Craft;
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
        $this->requirePermission('workflow-overview');

        $reviewsService = Workflow::$plugin->getReviews();

        $newReview = $reviewsService->getReviewById($newReviewId);
        $oldReview = $reviewsService->getReviewById($oldReviewId);

        if (!$newReview || !$oldReview || !$newReview->submissionId || $newReview->submissionId !== $oldReview->submissionId) {
            throw new NotFoundHttpException('Review not found');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $permissions = Workflow::$plugin->getSubmissionPermissions();

        if (!$currentUser || !$permissions->canViewReview($currentUser, $newReview) || !$permissions->canViewReview($currentUser, $oldReview)) {
            throw new ForbiddenHttpException('You are not allowed to compare these reviews.');
        }

        $variables = [
            'newReview' => $newReview,
            'oldReview' => $oldReview,
            'diff' => Workflow::$plugin->getContent()->getDiff(($oldReview->data ?? []), ($newReview->data ?? [])),
            'title' => "Compare review #{$oldReview->id} to #{$newReview->id}",
        ];

        return $this->renderTemplate('workflow/reviews/_compare', $variables);
    }

    public function actionDeleteReview(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('workflow-overview');

        $session = Craft::$app->getSession();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $reviewId = (int)$this->request->getRequiredBodyParam('reviewId');
        $review = Workflow::$plugin->getReviews()->getReviewById($reviewId);

        if (!$review) {
            throw new NotFoundHttpException('Review not found');
        }

        if (!$currentUser || !Workflow::$plugin->getSubmissionPermissions()->canDeleteReview($currentUser, $review)) {
            throw new ForbiddenHttpException('You are not allowed to delete this review.');
        }

        if (!Workflow::$plugin->getReviews()->deleteReview($review)) {
            $session->setError(Craft::t('workflow', 'Unable to delete review.'));

            return null;
        }

        $session->setNotice(Craft::t('workflow', 'Review deleted.'));

        return $this->redirectToPostedUrl();
    }

    public function actionGetCompareModalBody(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $view = $this->getView();
        $reviewsService = Workflow::$plugin->getReviews();

        $reviewId = (int)$this->request->getRequiredBodyParam('reviewId');
        $newReview = $reviewsService->getReviewById($reviewId);

        if (!$newReview) {
            throw new NotFoundHttpException('Review not found');
        }

        // Get the previous review
        $oldReview = $reviewsService->getPreviousReviewById($reviewId);
        $currentUser = Craft::$app->getUser()->getIdentity();
        $permissions = Workflow::$plugin->getSubmissionPermissions();

        if (!$currentUser || !$permissions->canViewReviewChanges($currentUser, $newReview) || ($oldReview && !$permissions->canViewReviewChanges($currentUser, $oldReview))) {
            throw new ForbiddenHttpException('You are not allowed to compare this review.');
        }

        $view->registerAssetBundle(\verbb\workflow\web\assets\cp\WorkflowAsset::class);

        $html = $view->renderTemplate('workflow/reviews/_compare-modal', [
            'review' => $newReview,
            'diff' => Workflow::$plugin->getContent()->getDiff(($oldReview->data ?? []), ($newReview->data ?? [])),
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
