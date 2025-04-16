<?php
namespace verbb\workflow\services;

use verbb\workflow\Workflow;
use verbb\workflow\base\ActionInterface;
use verbb\workflow\actions as actiontypes;
use verbb\workflow\elements\Submission;
use verbb\workflow\events\RegisterActionsEvent;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\helpers\Component as ComponentHelper;

class Actions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_EDITOR_ACTIONS = 'registerEditorActions';
    public const EVENT_REGISTER_REVIEWER_ACTIONS = 'registerReviewerActions';
    public const EVENT_REGISTER_PUBLISHER_ACTIONS = 'registerPublisherActions';


    // Properties
    // =========================================================================

    private array $_actions = [];


    // Public Methods
    // =========================================================================

    public function getRegisteredEditorActions(): array
    {
        $actions = [
            actiontypes\RevokeSubmission::class,
        ];

        $event = new RegisterActionsEvent([
            'actions' => $actions,
        ]);

        $this->trigger(self::EVENT_REGISTER_EDITOR_ACTIONS, $event);

        return $event->actions;
    }

    public function getRegisteredReviewerActions(): array
    {
        $actions = [
            actiontypes\ApproveReview::class,
            actiontypes\RejectReview::class,
        ];

        $event = new RegisterActionsEvent([
            'actions' => $actions,
        ]);

        $this->trigger(self::EVENT_REGISTER_REVIEWER_ACTIONS, $event);

        return $event->actions;
    }

    public function getRegisteredPublisherActions(): array
    {
        $actions = [
            actiontypes\ApproveSubmission::class,
            actiontypes\ApproveApplySubmission::class,
            actiontypes\RejectSubmission::class,
        ];

        $event = new RegisterActionsEvent([
            'actions' => $actions,
        ]);

        $this->trigger(self::EVENT_REGISTER_PUBLISHER_ACTIONS, $event);

        return $event->actions;
    }

    public function getEditorActions(): array
    {
        return $this->_getRoleActions('editor', [$this, 'getRegisteredEditorActions']);
    }

    public function getReviewerActions(): array
    {
        return $this->_getRoleActions('reviewer', [$this, 'getRegisteredReviewerActions']);
    }

    public function getPublisherActions(): array
    {
        return $this->_getRoleActions('publisher', [$this, 'getRegisteredPublisherActions']);
    }

    public function getEditorActionsMenuItems(ElementInterface $element, Submission $submission, Review $review): array
    {
        return $this->_getRoleActionMenuItems($this->getEditorActions(), $element, $submission, $review);
    }

    public function getReviewerActionsMenuItems(ElementInterface $element, Submission $submission, Review $review): array
    {
        return $this->_getRoleActionMenuItems($this->getReviewerActions(), $element, $submission, $review);
    }

    public function getPublisherActionsMenuItems(ElementInterface $element, Submission $submission, Review $review): array
    {
        return $this->_getRoleActionMenuItems($this->getPublisherActions(), $element, $submission, $review);
    }

    public function getActionById(string $id): ?ActionInterface
    {
        // Ensure all actions are loaded
        $this->getEditorActions();
        $this->getReviewerActions();
        $this->getPublisherActions();

        foreach ($this->_actions as $roleActions) {
            if (isset($roleActions[$id])) {
                return $roleActions[$id];
            }
        }

        return null;
    }


    // Public Methods
    // =========================================================================

    private function _getRoleActions(string $role, callable $fetcher): array
    {
        if (!isset($this->_actions[$role])) {
            $this->_actions[$role] = [];

            foreach ($fetcher() as $action) {
                $this->_actions[$role][$action::id()] = ComponentHelper::createComponent([
                    'type' => $action,
                ], ActionInterface::class);
            }
        }

        return $this->_actions[$role];
    }

    private function _getRoleActionMenuItems(array $actions, ElementInterface $element, Submission $submission, Review $review): array
    {
        $items = [];

        foreach ($actions as $action) {
            $items[] = $action->getMenuItem($element, $submission, $review);
        }

        return $items;
    }
}
