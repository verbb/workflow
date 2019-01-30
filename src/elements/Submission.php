<?php
namespace verbb\workflow\elements;

use verbb\workflow\elements\actions\SetStatus;
use verbb\workflow\elements\db\SubmissionQuery;
use verbb\workflow\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
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
    public $draftId;
    public $editorId;
    public $publisherId;
    public $status;
    public $notes;
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
        if ($this->getOwner()) {
            return $this->getOwner()->title;
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCpEditUrl()
    {
        $cpEditUrl = $url = $this->getOwner()->cpEditUrl;

        if ($this->draftId) {
            if (Craft::$app->getIsMultiSite()) {
                $cpEditUrl = explode('/', $cpEditUrl);
                array_pop($cpEditUrl);
                $cpEditUrl = implode('/', $cpEditUrl);
            }

            $url = $cpEditUrl . '/drafts/' . $this->draftId;
        }

        return $url;
    }

    public function getOwner()
    {
        if ($this->ownerId !== null) {
            return Craft::$app->getEntries()->getEntryById($this->ownerId, $this->ownerSiteId);
        }
    }

    public function getEditor()
    {
        if ($this->editorId !== null) {
            return Craft::$app->getUsers()->getUserById($this->editorId);
        }
    }

    public function getPublisher()
    {
        if ($this->publisherId !== null) {
            return Craft::$app->getUsers()->getUserById($this->publisherId);
        }
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
        $record->draftId = $this->draftId;
        $record->editorId = $this->editorId;
        $record->publisherId = $this->publisherId;
        $record->status = $this->status;
        $record->notes = $this->notes;
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
            'editor' => ['label' => Craft::t('workflow', 'Editor')],
            'dateCreated' => ['label' => Craft::t('workflow', 'Date Submitted')],
            'publisher' => ['label' => Craft::t('workflow', 'Publisher')],
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

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'publisher': {
                $publisher = $this->getPublisher();

                if ($publisher) {
                    return "<a href='" . $publisher->cpEditUrl . "'>" . $publisher . "</a>";
                } else {
                    return '-';
                }
            }
            case 'editor': {
                $editor = $this->getEditor();

                if ($editor) {
                    return "<a href='" . $editor->cpEditUrl . "'>" . $editor . "</a>";
                } else {
                    return '-';
                }
            }
            case 'dateApproved':
            case 'dateRejected': {
                return ($this->$attribute) ? parent::tableAttributeHtml($attribute) : '-';
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}