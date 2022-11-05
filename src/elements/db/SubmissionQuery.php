<?php
namespace verbb\workflow\elements\db;

use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SubmissionQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $ownerId = null;
    public mixed $ownerSiteId = null;
    public mixed $isComplete = null;
    public mixed $isPending = null;

    public mixed $ownerDraftId = null;
    public mixed $role = null;
    public mixed $editorId = null;
    public mixed $reviewerId = null;
    public mixed $publisherId = null;


    // Public Methods
    // =========================================================================

    public function ownerId($value): static
    {
        $this->ownerId = $value;
        return $this;
    }

    public function ownerSiteId($value): static
    {
        $this->ownerSiteId = $value;
        return $this;
    }

    public function isComplete($value): static
    {
        $this->isComplete = $value;
        return $this;
    }

    public function isPending($value): static
    {
        $this->isPending = $value;
        return $this;
    }

    public function ownerDraftId($value): static
    {
        $this->ownerDraftId = $value;
        return $this;
    }

    public function role($value): static
    {
        $this->role = $value;
        return $this;
    }

    public function editorId($value): static
    {
        $this->editorId = $value;
        return $this;
    }

    public function reviewerId($value): static
    {
        $this->reviewerId = $value;
        return $this;
    }

    public function publisherId($value): static
    {
        $this->publisherId = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('workflow_submissions');

        $this->query->select([
            'workflow_submissions.id',
            'workflow_submissions.ownerId',
            'workflow_submissions.ownerSiteId',
            'workflow_submissions.isComplete',
            'workflow_submissions.isPending',
            'workflow_submissions.dateCreated',
            'workflow_submissions.dateUpdated',
        ]);

        $reviewQuery = (new Query())
            ->select(['MAX(workflow_reviews.id)'])
            ->from('{{%workflow_reviews}} workflow_reviews')
            ->where('[[workflow_reviews.submissionId]] = [[elements.id]]');

        // Join the latest review along with each submission, we want to often query on that info
        $this->subQuery->leftJoin(['lastReview' => '{{%workflow_reviews}}'], '[[lastReview.id]] = (' . $reviewQuery->getRawSql() . ')');

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.ownerId', $this->ownerId));
        }

        if ($this->ownerSiteId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.ownerSiteId', $this->ownerSiteId));
        }

        if ($this->isComplete) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.isComplete', $this->isComplete));
        }

        if ($this->isPending) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.isPending', $this->isPending));
        }

        if ($this->ownerDraftId) {
            $this->subQuery->andWhere(Db::parseParam('lastReview.draftId', $this->ownerDraftId));
        }

        if ($this->role) {
            $this->subQuery->andWhere(Db::parseParam('lastReview.role', $this->role));
        }

        if ($this->editorId) {
            $reviewQuery = (clone $reviewQuery)
                ->andWhere(Db::parseParam('workflow_reviews.role', Review::ROLE_EDITOR))
                ->andWhere(Db::parseParam('workflow_reviews.userId', $this->editorId));

            $this->subQuery->innerJoin(['workflow_reviews' => '{{%workflow_reviews}}'], '[[workflow_reviews.id]] = (' . $reviewQuery->getRawSql() . ')');
        }

        if ($this->reviewerId) {
            $reviewQuery = (clone $reviewQuery)
                ->andWhere(Db::parseParam('workflow_reviews.role', Review::ROLE_REVIEWER))
                ->andWhere(Db::parseParam('workflow_reviews.userId', $this->reviewerId));

            $this->subQuery->innerJoin(['workflow_reviews' => '{{%workflow_reviews}}'], '[[workflow_reviews.id]] = (' . $reviewQuery->getRawSql() . ')');
        }

        if ($this->publisherId) {
            $reviewQuery = (clone $reviewQuery)
                ->andWhere(Db::parseParam('workflow_reviews.role', Review::ROLE_PUBLISHER))
                ->andWhere(Db::parseParam('workflow_reviews.userId', $this->publisherId));

            $this->subQuery->innerJoin(['workflow_reviews' => '{{%workflow_reviews}}'], '[[workflow_reviews.id]] = (' . $reviewQuery->getRawSql() . ')');
        }

        if ($this->_orderByLastReviewDate()) {
            $this->subQuery->addSelect(['lastReviewDate' => 'lastReview.dateCreated']);
        }

        return parent::beforePrepare();
    }


    // Protected Methods
    // =========================================================================

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            Review::STATUS_APPROVED => [
                'lastReview.status' => Review::STATUS_APPROVED,
            ],
            Review::STATUS_PENDING => [
                'lastReview.status' => Review::STATUS_PENDING,
            ],
            Review::STATUS_REJECTED => [
                'lastReview.status' => Review::STATUS_REJECTED,
            ],
            Review::STATUS_REVOKED => [
                'lastReview.status' => Review::STATUS_REVOKED,
            ],
            default => parent::statusCondition($status),
        };
    }


    // Private Methods
    // =========================================================================

    private function _orderByLastReviewDate(): bool|string
    {
        if ($this->orderBy) {
            if (is_string($this->orderBy)) {
                return strstr($this->orderBy, 'lastReviewDate');
            }

            if (is_array($this->orderBy)) {
                return isset($this->orderBy['lastReviewDate']);
            }
        }

        return false;
    }
}
