# Custom Actions

An action is something a person can do to a submission from the entry’s Workflow pane, such as **Approve and Publish**, **Reject** or **Revoke**. Workflow keeps a separate list of actions for each role. Editors can revoke their submission, reviewers can approve or reject a review, and publishers can approve, approve and publish, or reject. A custom action adds your own step to one of those lists, such as a publisher action that approves a submission without publishing it, or one that also notifies another system.

## Create the Action Class

Extend `verbb\workflow\base\Action`. Give the action an ID and a name, return the menu item that triggers it from `defineMenuItem()`, and do the work in `onBeforeSaveEntry()` or `onAfterSaveElement()`. When someone picks the action, Workflow saves the entry with the action’s ID in the request, checks that they’re allowed to perform it, then calls those methods around the save.

This publisher action approves a submission and completes it, but leaves the changes in their draft for someone to publish later.

```php
<?php
namespace modules\sitemodule\workflow;

use Craft;
use craft\base\ElementInterface;
use craft\events\ElementEvent;
use verbb\workflow\Workflow;
use verbb\workflow\base\Action;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Review;

class ApproveForLater extends Action
{
    public static function id(): string
    {
        return 'approve-for-later';
    }

    public static function displayName(): string
    {
        return Craft::t('site', 'Approve for Later');
    }

    public function onAfterSaveElement(ElementEvent $event): void
    {
        $approved = Workflow::$plugin->getSubmissions()->approveSubmission($event->element, false);

        $this->requireTransition($approved, $event->element);
    }

    protected function defineMenuItem(ElementInterface $element, Submission $submission, Review $review): array
    {
        return [
            'action' => $element->getIsDraft() ? 'elements/save-draft' : 'elements/save',
            'redirect' => '{cpEditUrl}',
        ];
    }
}
```

Passing `false` as the second argument to `approveSubmission()` marks the submission as approved without sending the published author notification. The editor notification is still sent when it’s enabled.

## Register the Action

Workflow finds publisher actions through the `registerPublisherActions` event. Register your class from your module or plugin’s `init()` method.

```php
use modules\sitemodule\workflow\ApproveForLater;
use verbb\workflow\events\RegisterActionsEvent;
use verbb\workflow\services\Actions;
use yii\base\Event;

Event::on(Actions::class, Actions::EVENT_REGISTER_PUBLISHER_ACTIONS, function(RegisterActionsEvent $event) {
    $event->actions[] = ApproveForLater::class;
});
```

Editor actions use the `registerEditorActions` event and reviewer actions use the `registerReviewerActions` event, in the same way. Each event’s `$event->actions` holds the class names of the role’s built-in actions, so you can also remove one, or reorder the list.

## Who Can Perform an Action

The event you register an action with sets its role, and Workflow checks that role before running the action. The submission has to be waiting for review, the person needs Craft’s permission to save the entry, and their role has to match.

- An editor action can be performed by the member of the site’s editor group who made the submission, or by an admin.
- A reviewer action can be performed by a member of the next reviewer group for the submission, or by an admin.
- A publisher action can be performed by a member of the site’s publisher group, or by an admin. An action whose ID starts with `approve-` also follows the publisher self-approval rules, so a publisher can only approve their own submission when **Approve own submissions** or the `definePublisherSelfApproval` event allows it.

Publisher actions appear in the **Actions** menu on the publisher’s Workflow pane, in the order they’re registered. The editor and reviewer panes show fixed buttons, so a custom editor or reviewer action doesn’t appear there. It runs when a request saves the entry with its ID in the `workflow-action` parameter, such as from your own form.

## Methods

::: reference
### `id()`

**Returns:** `string`

Returns the action’s unique ID, such as `approve-for-later`. Workflow sends this ID with the request and uses it to find the action again. This static method is required.
:::

::: reference
### `displayName()`

**Returns:** `string`

Returns the label shown for the action in the **Actions** menu. This static method comes from Craft’s `ComponentInterface`, so translate the label as you would any other control panel string.
:::

::: reference
### `defineMenuItem()`

**Arguments:** `ElementInterface $element`, `Submission $submission`, `Review $review`

**Returns:** `array`

Returns the menu item that triggers the action, given the entry, its submission and the submission’s latest review. Include an `action` (usually `elements/save-draft` for a draft, or `elements/save`) and a `redirect`. Workflow adds the label and the `workflow-action` parameter for you. Return an empty array to leave the action out of the menu for this entry.
:::

::: reference
### `onBeforeSaveEntry()`

**Arguments:** `ModelEvent $event`

**Returns:** `void`

Runs before the entry is saved, once Workflow has checked that the person can perform the action. `$event->sender` is the entry. Change the entry here, such as enabling it or setting a post date, or set `$event->isValid` to `false` to stop the save and the action. The default does nothing.
:::

::: reference
### `onAfterSaveElement()`

**Arguments:** `ElementEvent $event`

**Returns:** `void`

Runs after the entry is saved, which is where most actions change the submission. `$event->element` is the entry. During this call, the methods on `Workflow::$plugin->getSubmissions()` such as `approveSubmission()`, `rejectSubmission()`, `approveReview()` and `revokeSubmission()` act on the submission the action was performed on. The default does nothing.
:::

::: reference
### `requireTransition()`

**Arguments:** `bool $success`, `ElementInterface $element`

**Returns:** `void`

Throws an `InvalidElementException` with the message “Could not complete Workflow action.” when `$success` is `false`, so the person sees an error instead of a silent failure. Call it from `onAfterSaveElement()` with the result of a submission change and the entry.
:::
