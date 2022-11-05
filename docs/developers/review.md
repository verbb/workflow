# Review
A Review model is created for each submission to record the state of editors, reviewers and publishers.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the review.
`submission` | The submission this review is related to.
`submissionId` | The ID of the submission this review is related to.
`element` | The [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
`elementId` | The ID of the [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
`draftId` | The ID for the [Entry Draft](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on (if any).
`user` | The [User](https://docs.craftcms.com/api/v4/craft-elements-user.html) this review is related to.
`userId` | The ID of the [User](https://docs.craftcms.com/api/v4/craft-elements-user.html) this review is related to.
`role` | The Workflow "role" for this user. One of `editor`, `reviewer`, `publisher`.
`status` | The status of the review. One of `approved`, `pending`, `revoked`, `rejected`.
`notes` | Any notes left by the user.
`dateCreated` | The date this review was created.
