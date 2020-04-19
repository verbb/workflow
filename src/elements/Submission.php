<?php
namespace verbb\workflow\elements;

use verbb\workflow\elements\actions\SetStatus;
use verbb\workflow\elements\db\SubmissionQuery;
use verbb\workflow\models\Approval;
use verbb\workflow\records\Approval as ApprovalRecord;
use verbb\workflow\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

class Submission extends Element
{
    // Constants
    // =========================================================================

    const STATUS_APPROVED = 'approved';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REVOKED = 'revoked';


    // Public Properties
    // =========================================================================

    public $ownerId;
    public $ownerSiteId;
    public $ownerDraftId;
    public $editorId;
    public $publisherId;
    public $status;
    public $editorNotes;
    public $publisherNotes;
    public $data;
    public $dateApproved;
    public $dateRejected;
    public $dateRevoked;


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Workflow Submission');
    }

    public static function refHandle()
    {
        return 'submission';
    }

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_APPROVED => Craft::t('workflow', 'Approved'),
            self::STATUS_PENDING => Craft::t('workflow', 'Pending'),
            self::STATUS_REJECTED => Craft::t('workflow', 'Rejected'),
            self::STATUS_REVOKED => Craft::t('workflow', 'Revoked')
        ];
    }

    public static function find(): ElementQueryInterface
    {
        return new SubmissionQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('workflow', 'All submissions'),
            ]
        ];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('workflow', 'Are you sure you want to delete the selected submissions?'),
            'successMessage' => Craft::t('workflow', 'Submissions deleted.'),
        ]);

        $actions[] = SetStatus::class;

        return $actions;
    }


    // Public Methods
    // -------------------------------------------------------------------------

    public function __tostring()
    {
        if ($owner = $this->getOwner()) {
            return $owner->title;
        }

        return Craft::t('workflow', '[Deleted element]');
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCpEditUrl()
    {
        if ($owner = $this->getOwner()) {
            $url = $owner->getCpEditUrl();

            if ($this->ownerDraftId) {
                $url = UrlHelper::cpUrl($url, ['draftId' => $this->ownerDraftId]);
            }

            return $url;
        }

        return '';
    }

    public function getOwner()
    {
        if ($this->ownerId !== null) {
            return Craft::$app->getEntries()->getEntryById($this->ownerId, $this->ownerSiteId);
        }

        return null;
    }

    public function getEditor()
    {
        if ($this->editorId !== null) {
            return Craft::$app->getUsers()->getUserById($this->editorId);
        }

        return null;
    }

    public function getApprovals()
    {
        $approvals = [];

        $records = ApprovalRecord::find()
            ->where(['submissionId' => $this->id])
            ->all();

        foreach ($records as $record) {
            $approval = new Approval();
            $approval->setAttributes($record->getAttributes(), false);
            $approvals[] = $approval;
        }

        return $approvals;
    }

    public function getEditorUrl()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($editor = $this->getEditor()) {
            if ($currentUser->can('editUsers')) {
                return Html::a($editor, $editor->cpEditUrl);
            } else {
                return $editor;
            }
        }

        return '';
    }

    public function getPublisher()
    {
        if ($this->publisherId !== null) {
            return Craft::$app->getUsers()->getUserById($this->publisherId);
        }

        return null;
    }

    public function getPublisherUrl()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($publisher = $this->getPublisher()) {
            if ($currentUser->can('editUsers')) {
                return Html::a($publisher, $publisher->cpEditUrl);
            } else {
                return $publisher;
            }
        }

        return '';
    }

    public function getOwnerTitle()
    {
        return $this->getOwner()->title ?? '';
    }

    public function getEditorName()
    {
        return $this->getEditor()->fullName ?? '';
    }

    public function getPublisherName()
    {
        return $this->getPublisher()->fullName ?? '';
    }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $record = SubmissionRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid submission ID: ' . $this->id);
            }
        } else {
            $record = new SubmissionRecord();
            $record->id = $this->id;
        }

        $record->ownerId = $this->ownerId;
        $record->ownerSiteId = $this->ownerSiteId;
        $record->ownerDraftId = $this->ownerDraftId;
        $record->editorId = $this->editorId;
        $record->publisherId = $this->publisherId;
        $record->status = $this->status;
        $record->editorNotes = $this->editorNotes;
        $record->publisherNotes = $this->publisherNotes;
        $record->data = $this->data;
        $record->dateApproved = $this->dateApproved;
        $record->dateRejected = $this->dateRejected;
        $record->dateRevoked = $this->dateRevoked;

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('workflow', 'Entry')],
            'siteId' => ['label' => Craft::t('workflow', 'Site')],
            'editor' => ['label' => Craft::t('workflow', 'Editor')],
            'dateCreated' => ['label' => Craft::t('workflow', 'Date Submitted')],
            'publisher' => ['label' => Craft::t('workflow', 'Publisher')],
            'editorNotes' => ['label' => Craft::t('workflow', 'Editor Notes')],
            'publisherNotes' => ['label' => Craft::t('workflow', 'Publisher Notes')],
            'dateApproved' => ['label' => Craft::t('workflow', 'Date Approved')],
            'dateRejected' => ['label' => Craft::t('workflow', 'Date Rejected')],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'id' => Craft::t('workflow', 'Entry'),
            'editorId' => Craft::t('workflow', 'Editor'),
            'dateCreated' => Craft::t('workflow', 'Date Submitted'),
            'publisherId' => Craft::t('workflow', 'Publisher'),
            'dateApproved' => Craft::t('workflow', 'Date Approved'),
            'dateRejected' => Craft::t('workflow', 'Date Rejected'),
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['ownerTitle', 'editorName', 'publisherName'];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'publisher': {
                return $this->getPublisherUrl() ?: '-';
            }
            case 'editor': {
                return $this->getEditorUrl() ?: '-';
            }
            case 'dateApproved':
            case 'dateRejected': {
                return ($this->$attribute) ? parent::tableAttributeHtml($attribute) : '-';
            }
            case 'siteId': {
                if ($this->ownerSiteId) {
                    if ($site = Craft::$app->getSites()->getSiteById($this->ownerSiteId)) {
                        return $site->name;
                    }
                }

                return '';
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}
