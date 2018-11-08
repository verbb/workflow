# Events Reference

To learn more about how events work, see the [Craft documentation on events](https://docs.craftcms.com/v3/extend/updating-plugins.html#events).

### Submission::EVENT_BEFORE_SAVE

Raised before an editor submits an entry for approval. Event handlers can prevent the submission from getting sent by setting `$event->isValid` to false.

Params:

- `element` – The [Submission](/craft-plugins/workflow/docs/developers/submissions) element that is about to be submitted.
- `isNew` - Whether this is a new submission.

```php
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, function(Event $event) {
    $submission = $event->sender;
    $event->isValid = false;
});
```

### Submission::EVENT_AFTER_SAVE

Raised after an editor submits an entry for approval.

Params:

- `element` – The [Submission](/craft-plugins/workflow/docs/developers/submissions) element that has been sent.
- `isNew` - Whether this is a new submission.

```php
use verbb\workflow\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(Event $event) {
    $submission = $event->sender;
});
```
