# Events

Workflow provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Workflow’s behaviour.

## Submission Events

Submissions are Craft elements, so they have Craft’s usual element events as well as Workflow’s own.

### The `beforeSave` Event

The event that is triggered before a submission is saved. Workflow saves the submission each time it moves to a new stage, such as when it’s submitted, approved, rejected or revoked.

Set `$event->isValid` to `false` to stop the save. Workflow then treats the change as failed, so the review isn’t recorded and the person who acted on the entry sees an error.

```php
use craft\events\ModelEvent;
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    // Change the submission, or set `$event->isValid = false` to stop the save
});
```

### The `afterSave` Event

The event that is triggered after a submission is saved.

```php
use Craft;
use craft\events\ModelEvent;
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    Craft::info("Submission {$submission->id} is now {$submission->status}.", __METHOD__);
});
```

### The `afterGetReviewerUserGroups` Event

The event that is triggered after Workflow gets the reviewer user groups for a site, when it works out who reviews a submission next and who may act on it.

`$event->userGroups` holds the groups in review order, from the site’s **Reviewer User Groups** setting. Change the array to add, remove or reorder groups for this submission. `$event->submission` can be `null` when Workflow is checking a new entry that has no submission yet.

```php
use Craft;
use verbb\workflow\events\ReviewerUserGroupsEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_GET_REVIEWER_USER_GROUPS, function(ReviewerUserGroupsEvent $event) {
    $submission = $event->submission;

    // Add the Legal group as a final review stage for entries in the Policies section
    $legal = Craft::$app->getUserGroups()->getGroupByHandle('legal');

    if ($legal && $submission?->getOwner()?->getSection()?->handle === 'policies') {
        $event->userGroups[] = $legal;
    }
});
```

### The `definePublisherSelfApproval` Event

The event that is triggered when Workflow decides whether a publisher may approve a submission they also submitted as its editor. Set `$event->allowSelfApproval` to change the outcome for that request. It starts as `true` when the publisher has the **Approve own submissions** permission or belongs to one of the site’s `publisherSelfApprovalUserGroups`.

The event isn’t triggered when the submitting editor is a different person from `$event->user`, because self-approval doesn’t apply.

```php
use verbb\workflow\events\DefinePublisherSelfApprovalEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_DEFINE_PUBLISHER_SELF_APPROVAL, function(DefinePublisherSelfApprovalEvent $event) {
    // Let admins approve their own submissions on every site
    if ($event->user->admin) {
        $event->allowSelfApproval = true;
    }
});
```

## Action Events

These events register the actions each role can perform on a submission. [Custom Actions](docs:developers/custom-actions) explains how to write an action class.

### The `registerEditorActions` Event

The event that is triggered when Workflow registers the actions editors can perform, such as revoking a submission. Add a class to `$event->actions` to register an editor action. See [Custom Actions](docs:developers/custom-actions) for a complete example.

```php
use modules\sitemodule\workflow\MyEditorAction;
use verbb\workflow\events\RegisterActionsEvent;
use verbb\workflow\services\Actions;
use yii\base\Event;

Event::on(Actions::class, Actions::EVENT_REGISTER_EDITOR_ACTIONS, fn(RegisterActionsEvent $event) => $event->actions[] = MyEditorAction::class);
```

### The `registerReviewerActions` Event

The event that is triggered when Workflow registers the actions reviewers can perform, such as approving or rejecting a review. Add a class to `$event->actions` to register a reviewer action. See [Custom Actions](docs:developers/custom-actions) for a complete example.

```php
use modules\sitemodule\workflow\MyReviewerAction;
use verbb\workflow\events\RegisterActionsEvent;
use verbb\workflow\services\Actions;
use yii\base\Event;

Event::on(Actions::class, Actions::EVENT_REGISTER_REVIEWER_ACTIONS, fn(RegisterActionsEvent $event) => $event->actions[] = MyReviewerAction::class);
```

### The `registerPublisherActions` Event

The event that is triggered when Workflow registers the actions publishers can perform, such as approving or rejecting a submission. Add a class to `$event->actions` to register a publisher action. See [Custom Actions](docs:developers/custom-actions) for a complete example.

```php
use modules\sitemodule\workflow\ApproveForLater;
use verbb\workflow\events\RegisterActionsEvent;
use verbb\workflow\services\Actions;
use yii\base\Event;

Event::on(Actions::class, Actions::EVENT_REGISTER_PUBLISHER_ACTIONS, fn(RegisterActionsEvent $event) => $event->actions[] = ApproveForLater::class);
```

## Email Events

Each notification has a prepare event, triggered once with everyone who’ll receive it, and a before-send event, triggered for each message. Setting `$event->isValid` to `false` in a prepare event cancels the whole notification. In a before-send event, it skips only that recipient, and the others still get their message. Neither stops the submission change that caused the notification.

### The `prepareEditorEmail` Event

The event that is triggered before Workflow notifies an editor that a reviewer or publisher has approved or rejected their submission. `$event->editor` is the editor who made the submission, and you can replace it to send the notification to someone else.

Workflow also triggers this event before sending the published author notification, with `$event->editor` set to `null`. Check `$event->editor` to tell them apart.

```php
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_PREPARE_EDITOR_EMAIL, function(PrepareEmailEvent $event) {
    $editor = $event->editor;
    $submission = $event->submission;
    $review = $event->review;

    // Set `$event->isValid = false` to cancel the notification
});
```

### The `beforeSendEditorEmail` Event

The event that is triggered before an email is sent to an editor, and before the published author notification is sent to the entry’s author. `$event->mail` is the composed message, which you can change, such as adding a CC address. `$event->user` is the recipient.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_EDITOR_EMAIL, function(EmailEvent $event) {
    $event->mail->setBcc('editorial@example.com');
});
```

### The `prepareReviewerEmail` Event

The event that is triggered before Workflow notifies the next reviewer group that a submission is waiting for them. `$event->reviewers` is the list of users who’ll receive it, and you can change the list.

```php
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_PREPARE_REVIEWER_EMAIL, function(PrepareEmailEvent $event) {
    // Only notify reviewers who have turned on a `workflowNotifications` Lightswitch field on their account
    $event->reviewers = array_filter($event->reviewers, fn($user) => (bool)$user->getFieldValue('workflowNotifications'));
});
```

### The `beforeSendReviewerEmail` Event

The event that is triggered before an email is sent to a reviewer. `$event->mail` is the composed message and `$event->user` is the recipient.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_REVIEWER_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
});
```

### The `preparePublisherEmail` Event

The event that is triggered before Workflow notifies publishers that a submission is ready for approval. `$event->publishers` is the list of users who’ll receive it, and you can change the list.

```php
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_PREPARE_PUBLISHER_EMAIL, function(PrepareEmailEvent $event) {
    $publishers = $event->publishers;
    $submission = $event->submission;
});
```

### The `beforeSendPublisherEmail` Event

The event that is triggered before an email is sent to a publisher. `$event->mail` is the composed message and `$event->user` is the recipient.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
});
```

## Review Events

A review records one step in a submission’s history, such as an editor submitting the entry or a publisher approving it. These events can’t cancel the save or delete, but a before event can change the review before it’s saved.

### The `beforeSaveReview` Event

The event that is triggered before a review is saved. `$event->isNew` is `true` for a new review.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_BEFORE_SAVE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
    $isNew = $event->isNew;
});
```

### The `afterSaveReview` Event

The event that is triggered after a review is saved.

```php
use Craft;
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_AFTER_SAVE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;

    if ($event->isNew) {
        Craft::info("New {$review->role} review on submission {$review->submissionId}: {$review->status}.", __METHOD__);
    }
});
```

### The `beforeDeleteReview` Event

The event that is triggered before a review is deleted.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_BEFORE_DELETE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
});
```

### The `afterDeleteReview` Event

The event that is triggered after a review is deleted.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_AFTER_DELETE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
});
```
