# Events
Workflow provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Workflow’s behavior.

## Submission Events

### The `beforeSaveSubmission` event
The event that is triggered before a submission is saved. You can set `$event->isValid` to false to prevent saving.

```php
use craft\events\ModelEvent;
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    $event->isValid = false;
});
```

### The `afterSaveSubmission` event
The event that is triggered after a submission is saved.

```php
use craft\events\ModelEvent;
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;
});
```

### The `afterGetReviewerUserGroups` event
The event that is triggered after registering user groups for reviewers.

```php
use verbb\workflow\events\ReviewerUserGroupsEvent;
use verbb\workflow\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_GET_REVIEWER_USER_GROUPS, function(ReviewerUserGroupsEvent $event) {
    $submission = $event->submission;
    $userGroups = $event->userGroups;
});
```


## Email Events

### The `prepareEditorEmail` event
The event that is triggered when preparing the editor email.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_PREPARE_EDITOR_EMAIL, function(PrepareEmailEvent $event) {
    $editor = $event->editor;
    $submission = $event->submission;
    // ...
});
```

### The `beforeSendEditorEmail` event
The event that is triggered before an email is sent to an editor.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_EDITOR_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
    // ...
});
```

### The `prepareReviewerEmail` event
The event that is triggered when preparing the reviewer email.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_PREPARE_REVIEWER_EMAIL, function(PrepareEmailEvent $event) {
    $reviewers = $event->reviewers;
    $submission = $event->submission;
    // ...
});
```

### The `beforeSendReviewerEmail` event
The event that is triggered before an email is sent to a reviewer.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_REVIEWER_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
    // ...
});
```

### The `preparePublisherEmail` event
The event that is triggered when preparing the publisher email.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\PrepareEmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_PREPARE_PUBLISHER_EMAIL, function(PrepareEmailEvent $event) {
    $publishers = $event->publishers;
    $submission = $event->submission;
    // ...
});
```

### The `beforeSendPublisherEmail` event
The event that is triggered before an email is sent to a publisher.

The `isValid` event property can be set to `false` to prevent the email from being sent.

```php
use verbb\workflow\events\EmailEvent;
use verbb\workflow\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, function(EmailEvent $event) {
    $mail = $event->mail;
    $user = $event->user;
    $submission = $event->submission;
    // ...
});
```

## Review Events

### The `beforeSaveReview` event
The event that is triggered before a review is saved.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_BEFORE_SAVE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveReview` event
The event that is triggered after a review is saved.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_AFTER_SAVE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteReview` event
The event that is triggered before a review is deleted.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_BEFORE_DELETE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
    // ...
});
```

### The `afterDeleteReview` event
The event that is triggered after a review is deleted.

```php
use verbb\workflow\events\ReviewEvent;
use verbb\workflow\services\Reviews;
use yii\base\Event;

Event::on(Reviews::class, Reviews::EVENT_AFTER_DELETE_REVIEW, function(ReviewEvent $event) {
    $review = $event->review;
    // ...
});
```
