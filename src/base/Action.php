<?php
namespace verbb\workflow\base;

use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\events\ElementEvent;
use craft\events\ModelEvent;

abstract class Action extends Component implements ActionInterface
{
    // Abstract Methods
    // =========================================================================

    abstract public static function id(): string;


    // Public Methods
    // =========================================================================

    public function getMenuItem(ElementInterface $element, Submission $submission, Review $review): array
    {
        $menuItem = $this->defineMenuItem($element, $submission, $review);

        if ($menuItem) {
            $menuItem['label'] = static::displayName();
            $menuItem['params']['workflow-action'] = static::id();

            // `disclosuremenu` assumes you don't want to submit the main form
            $menuItem['attributes']['data']['form'] = 'main-form';

            // Prevent autosave behaviour in favour of full reload
            $menuItem['attributes']['data']['event-data'] = ['autosave' => false];
        }

        return $menuItem;
    }

    public function onBeforeSaveEntry(ModelEvent $event): void
    {

    }

    public function onAfterSaveElement(ElementEvent $event): void
    {

    }


    // Protected Methods
    // =========================================================================

    protected function defineMenuItem(ElementInterface $element, Submission $submission, Review $review): array
    {
        return [];
    }

}