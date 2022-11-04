<?php
namespace verbb\workflow\elements;

use verbb\workflow\Workflow;
use verbb\workflow\elements\actions\SetStatus;
use verbb\workflow\elements\db\SubmissionQuery;
use verbb\workflow\helpers\StringHelper;
use verbb\workflow\models\Review;
use verbb\workflow\records\Review as ReviewRecord;
use verbb\workflow\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\models\Site;

use DateTime;

use Exception;

class Submission extends Element
{
    // Constants
    // =========================================================================

    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVOKED = 'revoked';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Submission');
    }

    public static function refHandle(): ?string
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
            self::STATUS_REVOKED => Craft::t('workflow', 'Revoked'),
        ];
    }

    public static function find(): SubmissionQuery
    {
        return new SubmissionQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            '*' => [
                'key' => '*',
                'label' => Craft::t('workflow', 'All submissions'),
                'defaultSort' => ['dateCreated', 'desc'],
            ],
        ];
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = SetStatus::class;

        return $actions;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'ownerId' => ['label' => Craft::t('workflow', 'Entry')],
            'siteId' => ['label' => Craft::t('workflow', 'Site')],
            'dateCreated' => ['label' => Craft::t('workflow', 'Date Submitted')],
            'editor' => ['label' => Craft::t('workflow', 'Editor')],
            'lastReviewDate' => ['label' => Craft::t('workflow', 'Last Reviewed')],
            'reviewer' => ['label' => Craft::t('workflow', 'Last Reviewed By')],
            'publisher' => ['label' => Craft::t('workflow', 'Publisher')],
            'editorNotes' => ['label' => Craft::t('workflow', 'Editor Notes')],
            'publisherNotes' => ['label' => Craft::t('workflow', 'Publisher Notes')],
            'dateApproved' => ['label' => Craft::t('workflow', 'Date Approved')],
            'dateRejected' => ['label' => Craft::t('workflow', 'Date Rejected')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'ownerId',
            'editor',
            'dateCreated',
            'reviewer',
            'lastReviewDate',
            'publisher',
            'dateApproved',
            'dateRejected',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'ownerId' => Craft::t('workflow', 'Entry'),
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


    // Properties
    // =========================================================================

    public ?int $ownerId = null;
    public ?int $ownerSiteId = null;
    public ?int $ownerDraftId = null;
    public ?int $ownerCanonicalId = null;
    public ?int $editorId = null;
    public ?int $publisherId = null;
    public ?string $status = null;
    public ?array $data = null;
    public ?DateTime $dateApproved = null;
    public ?DateTime $dateRejected = null;
    public ?DateTime $dateRevoked = null;

    private mixed $_owner = null;
    private mixed $_editor = null;
    private mixed $_publisher = null;
    public ?string $_editorNotes = null;
    public ?string $_publisherNotes = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        if ($owner = $this->getOwner()) {
            return Craft::t('workflow', 'Submission for “' . $owner->title . '” on ' . Craft::$app->formatter->asDateTime($this->dateCreated, Locale::LENGTH_SHORT));
        }

        return Craft::t('workflow', '[Deleted element]');
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function canView(User $user): bool
    {
        return false;
    }

    public function canSave(User $user): bool
    {
        return false;
    }

    public function canDuplicate(User $user): bool
    {
        return false;
    }

    public function canDelete(User $user): bool
    {
        return true;
    }

    public function canCreateDrafts(User $user): bool
    {
        return false;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('workflow/' . $this->id);
    }

    public function getOwner(): ?Entry
    {
        if ($this->_owner !== null) {
            return $this->_owner;
        }

        if ($this->ownerId !== null) {
            return $this->_owner = Craft::$app->getEntries()->getEntryById($this->ownerId, $this->ownerSiteId);
        }

        return null;
    }

    public function setOwner(Entry $owner): void
    {
        $this->_owner = $owner;
        $this->ownerId = $owner->id;
        $this->ownerSiteId = $owner->siteId;
    }

    public function getEditor(): ?User
    {
        if ($this->_editor !== null) {
            return $this->_editor;
        }

        if ($this->editorId !== null) {
            return $this->_editor = Craft::$app->getUsers()->getUserById($this->editorId);
        }

        return null;
    }

    public function getEditorUrl(): string|User
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($editor = $this->getEditor()) {
            if ($currentUser->can('editUsers')) {
                return Html::a($editor, $editor->cpEditUrl);
            }

            return $editor;
        }

        return '';
    }

    public function getPublisher(): ?User
    {
        if ($this->_publisher !== null) {
            return $this->_publisher;
        }

        if ($this->publisherId !== null) {
            return $this->_publisher = Craft::$app->getUsers()->getUserById($this->publisherId);
        }

        return null;
    }

    public function getPublisherUrl(): string|User
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($publisher = $this->getPublisher()) {
            if ($currentUser->can('editUsers')) {
                return Html::a($publisher, $publisher->cpEditUrl);
            }

            return $publisher;
        }

        return '';
    }

    public function getOwnerTitle(): string
    {
        return $this->getOwner()->title ?? '';
    }

    public function getEditorName(): string
    {
        return $this->getEditor()->fullName ?? '';
    }

    public function getOwnerSite(): Site
    {
        return $this->getOwner()->getSite() ?? Craft::$app->getSites()->getPrimarySite();
    }

    public function getPublisherName(): string
    {
        return $this->getPublisher()->fullName ?? '';
    }

    public function setEditorNotes($value): void
    {
        $this->_editorNotes = StringHelper::sanitizeNotes($value);
    }

    public function getEditorNotes(bool $sanitize = true): ?string
    {
        return $sanitize ? StringHelper::unSanitizeNotes($this->_editorNotes) : $this->_editorNotes;
    }

    public function setPublisherNotes($value): void
    {
        $this->_publisherNotes = StringHelper::sanitizeNotes($value);
    }

    public function getPublisherNotes(bool $sanitize = true): ?string
    {
        return $sanitize ? StringHelper::unSanitizeNotes($this->_publisherNotes) : $this->_publisherNotes;
    }

    /**
     * Returns the reviews, optionally filtered by whether approved or not.
     *
     * @param bool|null
     * @return Review[]
     */
    public function getReviews(bool $approved = null): array
    {
        $reviews = [];

        $query = ReviewRecord::find()
            ->where(['submissionId' => $this->id])
            ->orderBy('dateCreated');

        if ($approved !== null) {
            $query->andWhere(['approved' => $approved]);
        }

        $records = $query->all();

        foreach ($records as $record) {
            $review = new Review();
            $review->setAttributes($record->getAttributes(), false);
            $reviews[] = $review;
        }

        return $reviews;
    }

    /**
     * Returns the last reviews, optionally filtered by whether approved or not.
     *
     * @param bool|null
     * @return Review|null
     */
    public function getLastReview(bool $approved = null): ?Review
    {
        $reviews = $this->getReviews($approved);

        return end($reviews) ?: null;
    }

    /**
     * Returns the last reviewer, optionally filtered by whether approved or not.
     *
     * @param bool|null
     * @return User|null
     */
    public function getLastReviewer(bool $approved = null): ?User
    {
        $lastReview = $this->getLastReview($approved);

        if ($lastReview === null) {
            return null;
        }

        return Craft::$app->getUsers()->getUserById($lastReview->userId);
    }

    /**
     * Returns the URL of the last reviewer, optionally filtered by whether approved or not.
     *
     * @param bool|null
     * @return User|string
     */
    public function getLastReviewerUrl(bool $approved = null): User|string
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($lastReviewer = $this->getLastReviewer($approved)) {
            if ($currentUser->can('editUsers')) {
                return Html::a($lastReviewer, $lastReviewer->cpEditUrl);
            }

            return $lastReviewer;
        }

        return '';
    }

    /**
     * Returns whether a user is allowed to review this submission.
     */
    public function canUserReview(User $user, $site): bool
    {
        $settings = Workflow::$plugin->getSettings();
        $publisherGroup = $settings->getPublisherUserGroup($site);

        if ($user->isInGroup($publisherGroup)) {
            return true;
        }

        $lastReviewer = $this->getLastReviewer();

        if ($lastReviewer === null) {
            return true;
        }

        $canReview = false;

        foreach (Workflow::$plugin->getSubmissions()->getReviewerUserGroups($site, $this) as $key => $userGroup) {
            if ($lastReviewer->isInGroup($userGroup)) {
                $canReview = false;
            } else if ($user->isInGroup($userGroup)) {
                $canReview = true;
            }
        }

        return $canReview;
    }

    public function afterSave(bool $isNew): void
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
        $record->ownerCanonicalId = $this->ownerCanonicalId;
        $record->editorId = $this->editorId;
        $record->publisherId = $this->publisherId;
        $record->status = $this->status;
        $record->editorNotes = $this->getEditorNotes(false);
        $record->publisherNotes = $this->getPublisherNotes(false);
        $record->data = $this->data;
        $record->dateApproved = $this->dateApproved;
        $record->dateRejected = $this->dateRejected;
        $record->dateRevoked = $this->dateRevoked;

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }


    // Protected Methods
    // =========================================================================

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'publisher':
                $user = $this->getPublisher();
                return $user ? Cp::elementHtml($user) : '-';
            case 'editor':
                $user = $this->getEditor();
                return $user ? Cp::elementHtml($user) : '-';
            case 'reviewer':
                $user = $this->getLastReviewer();
                return $user ? Cp::elementHtml($user) : '-';
            case 'lastReviewDate':
                $lastReview = $this->getLastReview();

                if ($lastReview === null) {
                    return '-';
                }

                $formatter = Craft::$app->getFormatter();
                return Html::tag('span', $formatter->asTimestamp($lastReview->dateCreated, Locale::LENGTH_SHORT), [
                    'title' => $formatter->asDatetime($lastReview->dateCreated, Locale::LENGTH_SHORT),
                ]);
            case 'dateApproved':
            case 'dateRejected':
                return ($this->$attribute) ? parent::tableAttributeHtml($attribute) : '-';
            case 'siteId':
                if ($this->ownerSiteId && $site = Craft::$app->getSites()->getSiteById($this->ownerSiteId)) {
                    return $site->name;
                }

                return '';
            case 'ownerId':
                if ($this->ownerId && $entry = Craft::$app->getEntries()->getEntryById($this->ownerId, $this->ownerSiteId)) {
                    return Cp::elementHtml($entry);
                }

                return '-';
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}
