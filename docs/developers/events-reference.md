# Events Reference

To learn more about how events work, see the [Craft documentation on events](http://buildwithcraft.com/docs/plugins/hooks-and-events#events).

### onBeforeSaveSubmission

Raised before an editor submits an entry for approval. Event handlers can prevent the submission from getting sent by setting `$event->performAction` to false.

Params:

- submission – The [SubmissionModel](05.-Submission-Model) that is about to be submitted.

```php
craft()->on('comments.onBeforeSaveSubmission', function($event) {
    $submission = $event->params['submission'];
    $event->performAction = false;
});
```

### onSaveSubmission

Raised after an editor submits an entry for approval.

Params:

- submission – The [SubmissionModel](05.-Submission-Model) that has been sent.

```php
craft()->on('comments.onSaveSubmission', function($event) {
    $submission = $event->params['submission'];
});
```

### onBeforeApproveSubmission

Raised before a publisher approves an entry. Event handlers can prevent the submission from being approved by setting `$event->performAction` to false.

Params:

- submission – The [SubmissionModel](05.-Submission-Model) that is about to be approved.

```php
craft()->on('comments.onBeforeApproveSubmission', function($event) {
    $submission = $event->params['submission'];
    $event->performAction = false;
});
```

### onApproveSubmission

Raised after a publisher has approved an entry.

Params:

- submission – The [SubmissionModel](05.-Submission-Model) that has been approved.

```php
craft()->on('comments.onApproveSubmission', function($event) {
    $submission = $event->params['submission'];
});
```