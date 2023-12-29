<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\helpers\StringHelper;
use verbb\workflow\models\Review;
use verbb\workflow\records\Review as ReviewRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\helpers\Db;

use yii\base\Component;

use Exception;
use Throwable;

class Reviews extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_REVIEW = 'beforeSaveReview';
    public const EVENT_AFTER_SAVE_REVIEW = 'afterSaveReview';
    public const EVENT_BEFORE_DELETE_REVIEW = 'beforeDeleteReview';
    public const EVENT_AFTER_DELETE_REVIEW = 'afterDeleteReview';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_reviews = null;


    // Public Methods
    // =========================================================================

    public function getAllReviews(): array
    {
        return $this->_reviews()->all();
    }

    public function getReviewsBySubmissionId(int $submissionId): array
    {
        return $this->_reviews()->where('submissionId', $submissionId)->all();
    }

    public function getReviewById(int $id): ?Review
    {
        return $this->_reviews()->firstWhere('id', $id);
    }

    public function getPreviousReviewById(int $id): ?Review
    {
        $currentReview = $this->getReviewById($id);

        if ($currentReview) {
            $reviews = $this->getReviewsBySubmissionId($currentReview->submissionId);

            foreach ($reviews as $key => $review) {
                if ($review->id === $id) {
                    return $reviews[$key + 1] ?? null;
                }
            }
        }

        return null;
    }

    public function getNextReviewById(int $id): ?Review
    {
        $currentReview = $this->getReviewById($id);

        if ($currentReview) {
            $reviews = $this->getReviewsBySubmissionId($currentReview->submissionId);

            foreach ($reviews as $key => $review) {
                if ($review->id === $id) {
                    return $reviews[$key - 1] ?? null;
                }
            }
        }

        return null;
    }

    public function saveReview(Review $review, bool $runValidation = true): bool
    {
        $isNewReview = !$review->id;

        // Fire a 'beforeSaveReview' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_REVIEW)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_REVIEW, new ReviewEvent([
                'review' => $review,
                'isNew' => $isNewReview,
            ]));
        }

        if ($runValidation && !$review->validate()) {
            Workflow::info('Review not saved due to validation error.');
            return false;
        }

        $reviewRecord = $this->_getReviewRecordById($review->id);
        $reviewRecord->id = $review->id;
        $reviewRecord->submissionId = $review->submissionId;
        $reviewRecord->elementId = $review->elementId;
        $reviewRecord->elementSiteId = $review->elementSiteId;
        $reviewRecord->draftId = $review->draftId;
        $reviewRecord->userId = $review->userId;
        $reviewRecord->role = $review->role;
        $reviewRecord->status = $review->status;
        $reviewRecord->notes = StringHelper::sanitizeNotes($review->getNotes(false));
        $reviewRecord->data = $review->data;

        $reviewRecord->save(false);

        if (!$review->id) {
            $review->id = $reviewRecord->id;
        }

        // Fire an 'afterSaveReview' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_REVIEW)) {
            $this->trigger(self::EVENT_AFTER_SAVE_REVIEW, new ReviewEvent([
                'review' => $review,
                'isNew' => $isNewReview,
            ]));
        }

        // Clear cache
        $this->_reviews = null;

        return true;
    }

    public function deleteReviewById(int $reviewId): bool
    {
        $review = $this->getReviewById($reviewId);

        if (!$review) {
            return false;
        }

        return $this->deleteReview($review);
    }

    public function deleteReview(Review $review): bool
    {
        // Fire a 'beforeDeleteReview' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_REVIEW)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_REVIEW, new ReviewEvent([
                'review' => $review,
            ]));
        }

        Db::delete('{{%workflow_reviews}}', ['id' => $review->id]);

        // Fire an 'afterDeleteReview' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_REVIEW)) {
            $this->trigger(self::EVENT_AFTER_DELETE_REVIEW, new ReviewEvent([
                'review' => $review,
            ]));
        }

        // Clear cache
        $this->_reviews = null;

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _reviews(): MemoizableArray
    {
        if (!isset($this->_reviews)) {
            $reviews = [];

            foreach ($this->_createReviewQuery()->all() as $result) {
                $reviews[] = new Review($result);
            }

            $this->_reviews = new MemoizableArray($reviews);
        }

        return $this->_reviews;
    }

    private function _createReviewQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'submissionId',
                'elementId',
                'elementSiteId',
                'draftId',
                'userId',
                'role',
                'status',
                'notes',
                'data',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%workflow_reviews}}'])
            ->orderBy('dateCreated desc');
    }

    private function _getReviewRecordById(int $reviewId = null): ?ReviewRecord
    {
        if ($reviewId !== null) {
            $reviewRecord = ReviewRecord::findOne(['id' => $reviewId]);

            if (!$reviewRecord) {
                throw new Exception(Craft::t('workflow', 'No review exists with the ID “{id}”.', ['id' => $reviewId]));
            }
        } else {
            $reviewRecord = new ReviewRecord();
        }

        return $reviewRecord;
    }
}
