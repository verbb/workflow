# Upgrading from v1
While the [changelog](https://github.com/verbb/workflow/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Concept
In Workflow 2, there's now a concept of [Reviews](docs:developers/review) in additionl to [Submissions](docs:developers/submission), to better represent the approval workflow.

In Workflow 1, a new submission was created each time an entry needed to be submitted for review. In addition, any actions (approved, rejected or revoked) would modify that single submission, leading to a lack of understanding of all the steps leading to a (hopefully) approved submission. For example, a review process might take several iterations to get right, all of which are tracked as separate submissions.

Now, in Workflow 2, a single [Submission](docs:developers/submission) is created by an editor, and used for the duration of the review process. The only way a submission can be completed (so an editor can start another submission process) is for the Editor to revoke the submission, or a Publisher approving it. Rejections and Reviewer approvals will not complete a submission.

In addition to this, each time an action is performed on an entry, a [Review](docs:developers/review) is created. This helps to keep track of everything that happens during the review process from start to finish. As such, a single submission can have multiple reviews. These are no longer stored on a submission, but on a review. We have added several methods to assist with getting the "latest" status or review.

For example, if you'd like to know the status of a submission, that's technically going to be the status of the most recent review on that submission. But you can still use `submission.status` to get this. Likewise, if you wanted to get the Editor, or Publisher from the most recent review, that's the same `submission.editor`, etc.

This should hopefully lead to a much better picture of the reviewal process.

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


## Submission Queries
The following [query params](docs:getting-elements/submission-queries) has been removed:

- `dateApproved`
- `dateRejected`
- `dateRevoked`


## Submissions
The following attributes for reviews have changed.

Old | What to do instead
--- | ---
| `dateApproved` | `lastReviewDate`
| `dateRejected` | `lastReviewDate`
| `dateRevoked` | `lastReviewDate`


## Reviews
The following attributes for reviews have changed.

Old | What to do instead
--- | ---
| `approved` | `status === 'approved'`

