<?php
namespace verbb\workflow\elements;

use verbb\workflow\Workflow;
use verbb\workflow\elements\actions\SetStatus;
use verbb\workflow\elements\db\SubmissionQuery;
use verbb\workflow\helpers\StringHelper;
use verbb\workflow\models\Review;
use verbb\workflow\records\Submission as SubmissionRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\actions\Delete;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\models\Site;

use DateTime;

use Exception;

class Submission extends Element
{
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

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return Review::statuses();
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
                'defaultSort' => ['lastReviewDate', 'desc'],
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
            'editor' => ['label' => Craft::t('workflow', 'Editor')],
            'reviewer' => ['label' => Craft::t('workflow', 'Last Reviewed By')],
            'publisher' => ['label' => Craft::t('workflow', 'Publisher')],
            'lastReviewDate' => ['label' => Craft::t('workflow', 'Last Reviewed')],
            'dateCreated' => ['label' => Craft::t('workflow', 'Date Submitted')],
            'status' => ['label' => Craft::t('workflow', 'Status')],
            'notes' => ['label' => Craft::t('workflow', 'Notes')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'ownerId',
            'editor',
            'reviewer',
            'publisher',
            'lastReviewDate',
            'dateCreated',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'ownerId' => Craft::t('workflow', 'Entry'),
            'editorId' => Craft::t('workflow', 'Editor'),
            'publisherId' => Craft::t('workflow', 'Publisher'),
            'lastReviewDate' => Craft::t('workflow', 'Last Reviewed'),
            'dateCreated' => Craft::t('workflow', 'Date Submitted'),
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['ownerTitle', 'editorName', 'publisherName'];
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'Submission';
    }


    // Properties
    // =========================================================================

    public ?int $ownerId = null;
    public ?int $ownerSiteId = null;
    public ?bool $isComplete = null;
    public ?bool $isPending = null;

    private ?array $_reviews = null;
    private ?ElementInterface $_owner = null;
    private ?ElementInterface $_draft = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->title = Craft::t('workflow', '[Deleted element]');

        if ($owner = $this->getOwner()) {
            $this->title = Craft::t('workflow', 'Submission for “{title}” on {date}', [
                'title' => $owner->title,
                'date' => Craft::$app->formatter->asDateTime($this->dateCreated, Locale::LENGTH_SHORT),
            ]);
        }
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canSave(User $user): bool
    {
        return true;
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
        return UrlHelper::cpUrl('workflow/submissions/edit/' . $this->id);
    }

    public function getSupportedSites(): array
    {
        if ($owner = $this->getOwner()) {
            return $owner->getSupportedSites();
        }

        return Craft::$app->getSites()->getAllSites();
    }

    public function setOwner(Entry $owner): void
    {
        $this->_owner = $owner;
        $this->ownerId = $owner->id;
        $this->ownerSiteId = $owner->siteId;
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

    public function getOwnerTitle(): string
    {
        return $this->getOwner()->title ?? '';
    }

    public function getOwnerSite(): Site
    {
        return $this->getOwner()->getSite() ?? Craft::$app->getSites()->getPrimarySite();
    }

    public function getDraft(): ?Entry
    {
        if ($this->_draft !== null) {
            return $this->_draft;
        }

        if ($lastReview = $this->getLastReview()) {
            if ($element = $lastReview->getElement()) {
                if (ElementHelper::isDraftOrRevision($element)) {
                    return $this->_draft = $element;
                }
            }
        }

        return null;
    }

    public function getOwnerCpUrl(bool $includeDraft = true): ?string
    {
        if ($includeDraft && $draft = $this->getDraft()) {
            return $draft->cpEditUrl;
        }

        if ($entry = $this->getOwner()) {
            return $entry->cpEditUrl;
        }

        return null;
    }

    public function getReviews(): array
    {
        if (!isset($this->_reviews)) {
            $this->_reviews = Workflow::$plugin->getReviews()->getReviewsBySubmissionId($this->id);
        }

        return $this->_reviews;
    }

    public function clearReviews(): void
    {
        $this->_reviews = null;
    }

    public function getLastReview(): ?Review
    {
        // Sorted by latest first be default
        $reviews = $this->getReviews();

        return $reviews[0] ?? null;
    }

    public function getLastReviewDate(): ?DateTime
    {
        if ($lastReview = $this->getLastReview()) {
            return $lastReview->dateCreated;
        }

        return null;
    }

    public function getEditor(): ?User
    {
        // As there will only ever be a single editor in a submission, find it
        foreach ($this->getReviews() as $review) {
            if ($review->role === Review::ROLE_EDITOR) {
                return $review->getUser();
            }
        }

        return null;
    }

    public function getReviewer(): ?User
    {
        if (($lastReview = $this->getLastReview()) && $lastReview->role === Review::ROLE_REVIEWER) {
            return $lastReview->getUser();
        }

        return null;
    }

    public function getPublisher(): ?User
    {
        if (($lastReview = $this->getLastReview()) && $lastReview->role === Review::ROLE_PUBLISHER) {
            return $lastReview->getUser();
        }

        return null;
    }

    public function getEditorId(): ?int
    {
        return $this->getEditor()->id ?? null;
    }

    public function getReviewerId(): ?int
    {
        return $this->getReviewer()->id ?? null;
    }

    public function getPublisherId(): ?int
    {
        return $this->getPublisher()->id ?? null;
    }

    public function getEditorName(): ?string
    {
        return $this->getEditor()->fullName ?? null;
    }

    public function getReviewerName(): ?string
    {
        return $this->getReviewer()->fullName ?? null;
    }

    public function getPublisherName(): ?string
    {
        return $this->getPublisher()->fullName ?? null;
    }

    public function getNotes(): ?string
    {
        if (($lastReview = $this->getLastReview())) {
            return $lastReview->getNotes();
        }

        return null;
    }

    public function getStatus(): ?string
    {
        if (($lastReview = $this->getLastReview())) {
            return $lastReview->status;
        }

        return null;
    }

    public function getRole(): ?string
    {
        if (($lastReview = $this->getLastReview())) {
            return $lastReview->role;
        }

        return null;
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

        $lastReviewer = $this->getReviewer();

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
        $record->isComplete = $this->isComplete;
        $record->isPending = $this->isPending;

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
            case 'notes':
                return Template::raw($this->getNotes());
            case 'reviewer':
                $user = $this->getReviewer();
                return $user ? Cp::elementHtml($user) : '-';
            case 'lastReviewDate':
                if ($lastReview = $this->getLastReview()) {
                    $formatter = Craft::$app->getFormatter();
                    return Html::tag('span', $formatter->asTimestamp($lastReview->dateCreated, Locale::LENGTH_SHORT), [
                        'title' => $formatter->asDatetime($lastReview->dateCreated, Locale::LENGTH_SHORT),
                    ]);
                }

                return '-';
            case 'siteId':
                if ($this->ownerSiteId && $site = Craft::$app->getSites()->getSiteById($this->ownerSiteId)) {
                    return $site->name;
                }

                return '';
            case 'ownerId':
                // Get the draft for the last review
                if ($element = $this->getDraft()) {
                    return Cp::elementHtml($element);
                }

                if ($element = $this->getOwner()) {
                    return Cp::elementHtml($element);
                }

                return '-';
            case 'status':
                $status = $this->getStatus();
                $statusDef = self::statuses()[$status] ?? null;
                $icon = Html::tag('span', '', ['class' => ['status', $statusDef['color'] ?? $status]]);
                $label = $statusDef['label'] ?? $statusDef ?? ucfirst($status);
                
                return $icon . Html::tag('span', $label);
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}
