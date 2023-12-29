<?php
namespace verbb\workflow\models;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\helpers\StringHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Template;

use DateTime;

use Twig\Markup;

class Review extends Model
{
    // Constants
    // =========================================================================

    public const ROLE_EDITOR = 'editor';
    public const ROLE_REVIEWER = 'reviewer';
    public const ROLE_PUBLISHER = 'publisher';

    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVOKED = 'revoked';


    // Static Methods
    // =========================================================================

    public static function statuses(): array
    {
        return [
            self::STATUS_APPROVED => Craft::t('workflow', 'Approved'),
            self::STATUS_PENDING => Craft::t('workflow', 'Pending'),
            self::STATUS_REJECTED => Craft::t('workflow', 'Rejected'),
            self::STATUS_REVOKED => Craft::t('workflow', 'Revoked'),
        ];
    }

    public static function roles(): array
    {
        return [
            self::ROLE_EDITOR => Craft::t('workflow', 'Editor'),
            self::ROLE_REVIEWER => Craft::t('workflow', 'Reviewer'),
            self::ROLE_PUBLISHER => Craft::t('workflow', 'Publisher'),
        ];
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $submissionId = null;
    public ?int $elementId = null;
    public ?int $elementSiteId = null;
    public ?int $draftId = null;
    public ?int $userId = null;
    public ?string $role = null;
    public ?string $status = null;
    public ?array $data = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private ?string $_notes = null;
    private ?Submission $_submission = null;
    private ?ElementInterface $_element = null;
    private ?ElementInterface $_user = null;


    // Public Methods
    // =========================================================================

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'notes';

        return $attributes;
    }

    public function setNotes($value): void
    {
        $this->_notes = $value;
    }

    public function getNotes(bool $sanitize = true): ?string
    {
        return $sanitize ? StringHelper::unSanitizeNotes($this->_notes) : $this->_notes;
    }

    public function getSubmission(): ?Submission
    {
        if ($this->_submission !== null) {
            return $this->_submission;
        }

        if ($this->submissionId !== null) {
            return $this->_submission = Workflow::$plugin->getSubmissions()->getSubmissionById($this->submissionId, $this->elementSiteId);
        }

        return null;
    }

    public function getElement(): ?ElementInterface
    {
        if ($this->_element !== null) {
            return $this->_element;
        }

        if ($this->elementId !== null) {
            return $this->_element = Craft::$app->getElements()->getElementById($this->elementId, null, $this->elementSiteId);
        }

        return null;
    }

    public function getUser(): ?User
    {
        if ($this->_user !== null) {
            return $this->_user;
        }

        if ($this->userId !== null) {
            return $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }

        return null;
    }

    public function getElementRevision(): ?ElementInterface
    {
        $element = $this->getElement();
        $attributes = $this->data;
        $fieldContent = ArrayHelper::remove($attributes, 'fields') ?? [];

        // The element/draft on the review might've been deleted (applied)
        if (!$element) {
            if ($submission = $this->getSubmission()) {
                $element = $submission->getOwner();
            }
        }

        if ($element) {
            $element->setAttributes($attributes, false);

            if ($fieldLayout = $element->getFieldLayout()) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $fieldValue = $fieldContent[$field->handle . ':' . $field->id] ?? null;

                    $element->setFieldValue($field->handle, $fieldValue);
                }
            }
        }

        return $element;
    }

    public function getUserUrl(): ?Markup
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($user = $this->getUser()) {
            if ($currentUser->can('editUsers')) {
                return Template::raw(Html::a($user, $user->getCpEditUrl()));
            }

            return Template::raw($user);
        }

        return null;
    }

    public function getStatusName(): ?string
    {
        return self::statuses()[$this->status] ?? ucfirst($this->status);
    }

    public function getRoleName(): ?string
    {
        return self::roles()[$this->role] ?? ucfirst($this->role);
    }

    public function hasChanges(): bool
    {
        $oldReview = Workflow::$plugin->getReviews()->getPreviousReviewById($this->id);

        if ($oldReview) {
            return (bool)Workflow::$plugin->getContent()->getDiff($oldReview->data, $this->data);
        }

        return false;
    }

}
