# Submission
Whenever you're dealing with a submission in your template, you're actually working with a `Submission` object.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the submission.
`owner` | The [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
`ownerId` | The [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) ID this submission was made on.
`ownerSiteId` | The site ID for the entry this submission was made on.
`isComplete` | Whether the submission is considered complete. This can be done only through approving or revoking a submission.
`isPending` | Whether the submission is considered pending (a submission is currently waiting for approval/review).
`dateCreated` | The date this submission was submitted by an editor.

## Methods

Method | Description
--- | ---
`getReviews()` | Returns a collection of [Review](docs:developers/review) objects.
`getLastReview()` | Returns the most recent (last-submitted) [Review](docs:developers/review).


In additon, there are several other attributes and functions that are set at the submission level, but they in fact target the last (current) [Review](docs:developers/review). They are available here for convenience.

Attribute | Description
--- | ---
`editor` | The user as the editor role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
`editorId` | The ID of the user as the editor role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
`reviewer` | The user as the reviewer role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
`reviewerId` | The ID of the ruser as the eviewer role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
`publisher` | The user as the publisher role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
`publisherId` | The ID of the user as the publisher role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
`notes` | The notes of this most recent (last-submitted) [Review](docs:developers/review).
`status` | The status of this most recent (last-submitted) [Review](docs:developers/review).
`role` | The role of this most recent (last-submitted) [Review](docs:developers/review).
