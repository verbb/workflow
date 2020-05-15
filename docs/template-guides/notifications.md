# Email Notifications

Workflow has a number of email notifications, sent off at different events. Craft provides an easy way to manage these notifications, directly in the control panel, through System Messages. These templates are edited in the control panel, and can be different per site (for multi-site Craft sites). The added bonus is that you can use specific variables in your templates.

Workflow provides example email content as part of the plugin, but you of course can change these to suit your needs.

To access these, visit Utilities → System Messages.

### When an editor submits entry for approval

Variable | Description
--- | ---
`submission` | A [Submission](docs:developers/submission) element.
`user` | A [User](https://docs.craftcms.com/api/v3/craft-elements-user.html) element.

#### Example Subject

```twig
"{{ submission.owner.title }}" has been submitted for approval on {{ siteName }}.
```

#### Example Body

```twig
Hey {{ user.friendlyName }},

{{ submission.editor }} has submitted the entry "{{ submission.owner.title }}" for approval on {{ siteName }}.

{% if submission.editorNotes %}Notes: "{{ submission.editorNotes }}"

{% endif %}To review it please log into your control panel.

{{ submission.cpEditUrl }}
```

### When a reviewer approves or rejects an editor submission

Variable | Description
--- | ---
`submission` | A [Submission](docs:developers/submission) element.
`review` | A [Submission](docs:developers/review) model.
`user` | A [User](https://docs.craftcms.com/api/v3/craft-elements-user.html) element.

#### Example Subject

```twig
Your submission for "{{ submission.owner.title }}" has been {{ review.approved ? 'approved' : 'rejected' }} on {{ siteName }}.
```

#### Example Body

```twig
Hey {{ user.friendlyName }},

Your submission for {{ submission.owner.title }} has been {{ review.approved ? 'approved' : 'rejected' }} {{ review.dateCreated | date }} on {{ siteName }}.

{% if review.notes %}Notes: "{{ review.notes }}"

{% endif %}View your submission by logging into your control panel.

{{ submission.cpEditUrl }}
```

### When a publisher approves or rejects an editor submission

Variable | Description
--- | ---
`submission` | A [Submission](docs:developers/submission) element.
`user` | A [User](https://docs.craftcms.com/api/v3/craft-elements-user.html) element.

#### Example Subject

```twig
Your submission for "{{ submission.owner.title }}" has been {{ submission.status }} on {{ siteName }}.
```

#### Example Body

```twig
Hey {{ user.friendlyName }},

Your submission for {{ submission.owner.title }} has been {{ submission.status }} {{ (submission.status == 'approved') ? submission.dateApproved | date : submission.dateRejected | date }} on {{ siteName }}.

{% if submission.publisherNotes %}Notes: "{{ submission.publisherNotes }}"

{% endif %}View your submission by logging into your control panel.

{{ submission.cpEditUrl }}
```
