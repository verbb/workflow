<?php
namespace verbb\workflow\elements\db;

use verbb\workflow\elements\Submission;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SubmissionQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $ownerId = null;
    public mixed $ownerSiteId = null;
    public mixed $ownerDraftId = null;
    public mixed $editorId = null;
    public mixed $publisherId = null;
    public mixed $editorNotes = null;
    public mixed $publisherNotes = null;
    public mixed $data = null;
    public mixed $dateApproved = null;
    public mixed $dateRejected = null;
    public mixed $dateRevoked = null;


    // Public Methods
    // =========================================================================

    public function ownerId($value): static
    {
        $this->ownerId = $value;
        return $this;
    }

    public function ownerDraftId($value): static
    {
        $this->ownerDraftId = $value;
        return $this;
    }

    public function ownerSiteId($value): static
    {
        $this->ownerSiteId = $value;
        return $this;
    }

    public function editorId($value): static
    {
        $this->editorId = $value;
        return $this;
    }

    public function publisherId($value): static
    {
        $this->publisherId = $value;
        return $this;
    }

    public function dateApproved($value): static
    {
        $this->dateApproved = $value;
        return $this;
    }

    public function dateRejected($value): static
    {
        $this->dateRejected = $value;
        return $this;
    }

    public function dateRevoked($value): static
    {
        $this->dateRevoked = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('workflow_submissions');

        $this->query->select([
            'workflow_submissions.*',
        ]);

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.ownerId', $this->ownerId));
        }

        if ($this->ownerDraftId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.ownerDraftId', $this->ownerDraftId));
        }

        if ($this->ownerSiteId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.ownerSiteId', $this->ownerSiteId));
        }

        if ($this->editorId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.editorId', $this->editorId));
        }

        if ($this->publisherId) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.publisherId', $this->publisherId));
        }

        if ($this->editorNotes) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.editorNotes', $this->editorNotes));
        }

        if ($this->publisherNotes) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.publisherNotes', $this->publisherNotes));
        }

        if ($this->data) {
            $this->subQuery->andWhere(Db::parseParam('workflow_submissions.data', $this->data));
        }

        if ($this->dateApproved) {
            $this->subQuery->andWhere(Db::parseDateParam('workflow_submissions.dateApproved', $this->dateApproved));
        }

        if ($this->dateRejected) {
            $this->subQuery->andWhere(Db::parseDateParam('workflow_submissions.dateRejected', $this->dateRejected));
        }

        if ($this->dateRevoked) {
            $this->subQuery->andWhere(Db::parseDateParam('workflow_submissions.dateRevoked', $this->dateRevoked));
        }

        return parent::beforePrepare();
    }


    // Protected Methods
    // =========================================================================

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            Submission::STATUS_APPROVED => [
                'workflow_submissions.status' => Submission::STATUS_APPROVED,
            ],
            Submission::STATUS_PENDING => [
                'workflow_submissions.status' => Submission::STATUS_PENDING,
            ],
            Submission::STATUS_REJECTED => [
                'workflow_submissions.status' => Submission::STATUS_REJECTED,
            ],
            Submission::STATUS_REVOKED => [
                'workflow_submissions.status' => Submission::STATUS_REVOKED,
            ],
            default => parent::statusCondition($status),
        };
    }
}
