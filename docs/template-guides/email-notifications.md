# Email Notifications
Workflow has a number of email notifications, sent off at different events. Craft provides an easy way to manage these notifications, directly in the control panel, through System Messages. These templates are edited in the control panel, and can be different per site (for multi-site Craft sites). The added bonus is that you can use specific variables in your templates.

Workflow provides example email content as part of the plugin, but you of course can change these to suit your needs.

To access these, visit Utilities → System Messages.

::: tip
When Craft’s `enableTwigSandbox` setting is on, these messages can only read the values used in the examples below, plus what Craft allows on its own elements. Print a site or user through a property such as `submission.ownerSite.name` or `review.user.name`, not on its own. You can allow more through `config/twig-sandbox.php`.
:::

## When an Editor Submits Entry for Approval

Variable | Description
--- | ---
`submission` | A [Submission](docs:reference/submission) element.
`review` | A [Review](docs:reference/review) model.
`user` | A [User](https://docs.craftcms.com/api/v5/craft-elements-user.html) element.

### Example Subject

```twig
"{{ submission.ownerTitle }}" has been submitted for approval on {{ submission.ownerSite.name }}.
```

### Example Body

```twig
Hey {{ user.friendlyName }},

{{ review.user.name }} has submitted the entry "{{ submission.ownerTitle }}" for approval on {{ submission.ownerSite.name }}.

{% if review.notes %}{{ review.roleName }} Notes: "{{ review.notes }}"

{% endif %}To review it please log into your control panel.

{{ submission.ownerCpUrl }}
```

## When a Reviewer Approves or Rejects an Editor Submission

Variable | Description
--- | ---
`submission` | A [Submission](docs:reference/submission) element.
`review` | A [Review](docs:reference/review) model.
`user` | A [User](https://docs.craftcms.com/api/v5/craft-elements-user.html) element.

### Example Subject

```twig
Your submission for "{{ submission.ownerTitle }}" has been {{ review.status }} on {{ submission.ownerSite.name }}.
```

### Example Body

```twig
Hey {{ user.friendlyName }},

Your submission for {{ submission.ownerTitle }} has been {{ review.status }} {{ review.dateCreated | date }} on {{ submission.ownerSite.name }}.

{% if review.notes %}{{ review.roleName }} Notes: "{{ review.notes }}"

{% endif %}View your submission by logging into your control panel.

{{ submission.ownerCpUrl }}
```

## When a Publisher Approves or Rejects an Editor Submission

Variable | Description
--- | ---
`submission` | A [Submission](docs:reference/submission) element.
`review` | A [Review](docs:reference/review) model.
`user` | A [User](https://docs.craftcms.com/api/v5/craft-elements-user.html) element.

### Example Subject

```twig
Your submission for "{{ submission.ownerTitle }}" has been {{ review.status }} on {{ submission.ownerSite.name }}.
```

### Example Body

```twig
Hey {{ user.friendlyName }},

Your submission for {{ submission.ownerTitle }} has been {{ review.status }} {{ review.dateCreated | date }} on {{ submission.ownerSite.name }}.

{% if review.notes %}{{ review.roleName }} Notes: "{{ review.notes }}"

{% endif %}View your submission by logging into your control panel.

{{ submission.ownerCpUrl }}
```
