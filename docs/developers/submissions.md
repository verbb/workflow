# Submissions

Submissions can be queried:

```twig
{% set submissions = craft.workflow.submissions({
    ownerId: entry.id,
    limit: 10,
    status: 'pending'
}) %}

{% for submission in submissions %}
    {{ submission.notes }}
{% endfor %}
```

Submissions also have the following attributes:

### Attributes

Attribute | Description
--- | ---
`id` | ID of the submission.
`ownerId` | The entry ID this submission was made on.
`owner` | [Entry](https://craftcms.com/docs/templating/entrymodel) this submission was made on.
`draftId` | The ID for the Entry Draft this submission was made on (if any).
`editorId` | The user ID for the editor who made this submission.
`editor` | [User](https://craftcms.com/docs/templating/usermodel) for the editor who made this submission.
`publisherId` | The user ID for the editor who made this submission.
`publisher` | [User](https://craftcms.com/docs/templating/usermodel) for the publisher who approved this submission (if any).
`status` | The status of this submission (Approved or Pending).
`notes` | Any notes left by users on submissions.
`dateApproved` | The date this submission has been approved (if approved by publisher).
`dateRejected` | The date this submission has been rejected (if rejected by publisher).
`dateRevoked` | The date this submission has been revoked (if revoked by editor).
`dateCreated` | The date this submission was submitted by an editor.
