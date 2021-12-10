# Events

Events can be used to extend the functionality of Workflow.

## Submission related events

### The `beforeSaveSubmission` event

Plugins can get notified before a submission is saved. Event handlers can prevent the submission from getting sent by setting `$event->isValid` to false.

```php
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, function(Event $e) {
    $submission = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveSubmission` event

Plugins can get notified after a submission has been saved

```php
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(Event $e) {
    $submission = $event->sender;
});
```

### The `afterGetReviewerUserGroups` event

Plugins can get notified when registering user groups for reviewers.

```php
use verbb\workflow\events\ReviewerUserGroupsEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_GET_REVIEWER_USER_GROUPS, function(ReviewerUserGroupsEvent $e) {
    $submission = $event->submission;
    $userGroups = $event->userGroups;

});
```

### The `beforeSendEditorEmail` event
The event that is triggered before an email is sent to an editor.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_EDITOR_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
    // ...
});
```


### The `beforeSendReviewerEmail` event
The event that is triggered before an email is sent to a reviewer.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_REVIEWER_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
    // ...
});
```


### The `beforeSendPublisherEmail` event
The event that is triggered before an email is sent to a publisher.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
    // ...
});
```
