# Submission Model

SubmissionModel’s have the following attributes and methods:

### Attributes

Attribute | Description
--- | ---
`id` | ID of the submission.
`owner` | [EntryModel](https://craftcms.com/docs/templating/entrymodel) this submission was made on.
`draftId` | The ID for the Entry Draft this submission was made on (if any).
`editor` | [UserModel](https://craftcms.com/docs/templating/usermodel) for the editor who made this submission.
`publisher` | [UserModel](https://craftcms.com/docs/templating/usermodel) for the publisher who approved this submission (if any).
`status` | The status of this submission (Approved or Pending).
`dateApproved` | The date this submission has been approved (if approved by publisher).
`dateCreated` | The date this submission was submitted by an editor.