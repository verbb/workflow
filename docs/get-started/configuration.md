# Configuration

You can customise Workflow’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `workflow.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will change the name displayed in the control panel:

```php
<?php

return [
    'pluginName' => 'Workflow Tools',
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

### General

::: reference
### `editorUserGroup`

**Type:** `array` · **Default:** `[]`

An array of user groups for editors.
:::


::: reference
### `reviewerUserGroups`

**Type:** `array` · **Default:** `[]`

An array of user groups for each reviewer.
:::


::: reference
### `publisherUserGroup`

**Type:** `array` · **Default:** `[]`

An array of user groups for publishers.
:::


::: reference
### `editorNotesRequired`

**Type:** `array` · **Default:** `[]`

An array for whether editors are required to enter a note in their submissions.
:::


::: reference
### `publisherNotesRequired`

**Type:** `array` · **Default:** `[]`

An array for whether publishers are required to enter a note in their submissions.
:::


::: reference
### `lockDraftSubmissions`

**Type:** `bool` · **Default:** `true`

Whether an entry should be locked for editing after it‘s been submitted for review.
:::


::: reference
### `publisherSelfApprovalUserGroups`

**Type:** `array` · **Default:** `[]`

Per site, user groups whose members may approve their own submissions (same nested shape as `reviewerUserGroups`). Combined with the **Approve own submissions** permission and the `definePublisherSelfApproval` event.
:::


### Notifications

::: reference
### `editorNotifications`

**Type:** `bool` · **Default:** `true`

Whether email notifications should be delivered to individual editors when approved or rejected.
:::


::: reference
### `editorNotificationsOptions`

**Type:** `array` · **Default:** `[]`

Whether editor notifications should include the reviewer's or publisher's email whose triggered the action.
:::


::: reference
### `reviewerNotifications`

**Type:** `bool` · **Default:** `true`

Whether email notifications should be delivered to reviewers when editors submit an entry for review.
:::


::: reference
### `reviewerApprovalNotifications`

**Type:** `bool` · **Default:** `false`

Whether email notifications should be delivered to editors when each reviewer approves an entry after review.
:::


::: reference
### `publisherNotifications`

**Type:** `bool` · **Default:** `true`

Whether email notifications should be delivered to publishers when editors submit an entry for review.
:::


::: reference
### `publishedAuthorNotifications`

**Type:** `bool` · **Default:** `false`

Whether email notifications should be delivered to the entry author when approved and published by a Publisher.
:::


::: reference
### `publisherNotificationsUserGroup`

**Type:** `string|null` · **Default:** `null`

The user group UID to have email notifications for publishers sent to. By default, all users in the `publisherUserGroup` will receive email notifications.
:::


### Permissions

::: reference
### `enabledSections`

**Type:** `mixed` · **Default:** `'*'`

An array of section UIDs to enable submissions on. Use '\*' for all.
:::


### Multi Site Options
For some settings like `editorUserGroup`, `reviewerUserGroups`, `publisherUserGroup`, etc. - these are multi-site configurable. You should provide a nested array of User Group UIDs with Site UIDs. For example:

```php
return [
    '*' => [
        'editorUserGroup' => [
            // Site UID => User Group UID
            '76974830-73a5-45fb-9c73-72ac8c8981dc' => '1d9e7ded-b621-48ee-9253-12a6e8f8094e',
        ],

        'reviewerUserGroups' => [
            // Site UID => Collection of User Group UIDs
            '76974830-73a5-45fb-9c73-72ac8c8981dc' => [
                ['87524453-a67b-4416-8a6e-de44c3547d18'],
                ['c23a4d8d-47f0-4e71-927c-d5897ec9c9f8'],
            ],
        ],

        'publisherUserGroup' => [
            // Site UID => User Group UID
            '76974830-73a5-45fb-9c73-72ac8c8981dc' => '8ffaff7f-b68e-4ed0-a74b-4e5596e01735',
        ],

        'editorNotesRequired' => [
            // Site UID => true/false
            '76974830-73a5-45fb-9c73-72ac8c8981dc' => true,
        ],

        'publisherNotesRequired' => [
            // Site UID => true/false
            '76974830-73a5-45fb-9c73-72ac8c8981dc' => true,
        ],

        'publisherSelfApprovalUserGroups' => [
            // Site UID => rows of User Group UIDs (optional extra groups that may self-approve)
            '76974830-73a5-45fb-9c73-72ac8c8981dc' => [
                ['8ffaff7f-b68e-4ed0-a74b-4e5596e01735'],
            ],
        ],
    ]
];
```

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Workflow.
