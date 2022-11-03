# Upgrading from v1
While the [changelog](https://github.com/verbb/workflow/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Events
The following events have changed:

```php
// Workflow v1
use verbb\workflow\services\Submissions;

Event::on(Submissions::class, Submissions::EVENT_PREPARE_EDITOR_EMAIL, function(EmailEvent $event)
Event::on(Submissions::class, Submissions::EVENT_PREPARE_REVIEWER_EMAIL, function(EmailEvent $event)
Event::on(Submissions::class, Submissions::EVENT_PREPARE_PUBLISHER_EMAIL, function(EmailEvent $event)
Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_EDITOR_EMAIL, function(EmailEvent $event)
Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_REVIEWER_EMAIL, function(EmailEvent $event)
Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, function(EmailEvent $event)

// Workflow v2
use verbb\workflow\services\Emails;

Event::on(Emails::class, Emails::EVENT_PREPARE_EDITOR_EMAIL, function(EmailEvent $event)
Event::on(Emails::class, Emails::EVENT_PREPARE_REVIEWER_EMAIL, function(EmailEvent $event)
Event::on(Emails::class, Emails::EVENT_PREPARE_PUBLISHER_EMAIL, function(EmailEvent $event)
Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_EDITOR_EMAIL, function(EmailEvent $event)
Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_REVIEWER_EMAIL, function(EmailEvent $event)
Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_PUBLISHER_EMAIL, function(EmailEvent $event)
```

## Front-end Submissions
The [front-end submissions](docs:template-guides/front-end-submission) should use the correct `action` endpoint for Craft.

Old | What to do instead
--- | ---
| `entry-revisions/save-draft` | `entries/save-entry`
