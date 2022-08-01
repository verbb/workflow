# Submission
Whenever you're dealing with a submission in your template, you're actually working with a `Submission` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the submission.
`ownerId` | The entry ID this submission was made on.
`owner` | [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
`draftId` | The ID for the Entry Draft this submission was made on (if any).
`editorId` | The user ID for the editor who made this submission.
`editor` | [User](https://docs.craftcms.com/api/v4/craft-elements-user.html) for the editor who made this submission.
`publisherId` | The user ID for the editor who made this submission.
`publisher` | [User](https://docs.craftcms.com/api/v4/craft-elements-user.html) for the publisher who approved this submission (if any).
`status` | The status of this submission (Approved or Pending).
`editorNotes` | Any notes left by editors on submissions.
`publisherNotes` | Any notes left by publishers on submissions.
`dateApproved` | The date this submission has been approved (if approved by publisher).
`dateRejected` | The date this submission has been rejected (if rejected by publisher).
`dateRevoked` | The date this submission has been revoked (if revoked by editor).
`dateCreated` | The date this submission was submitted by an editor.
