# Configuration
Create a `workflow.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Workflow, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        // General
        'editorUserGroup' => [],
        'reviewerUserGroups' => [],
        'publisherUserGroup' => [],
        'editorNotesRequired' => [],
        'publisherNotesRequired' => [],
        'lockDraftSubmissions' => true,

        // Notifications
        'editorNotifications' => true,
        'editorNotificationsOptions' => [],
        'reviewerNotifications' => true,
        'reviewerApprovalNotifications' => false,
        'publisherNotifications' => true,
        'publishedAuthorNotifications' => false,
        'selectedPublishers' => '*',

        // Permissions
        'enabledSections' => '*',
    ]
];
```

## Configuration options

General
- `editorUserGroup` - An array of user groups for editors.
- `reviewerUserGroups` - An array of user groups for each reviewer.
- `publisherUserGroup` - An array of user groups for publishers.
- `editorNotesRequired` - An array for whether editors are required to enter a note in their submissions.
- `publisherNotesRequired` - An array for whether publishers are required to enter a note in their submissions.
- `lockDraftSubmissions` - Whether an entry should be locked for editing after it‘s been submitted for review.

Notifications
- `editorNotifications` - Whether email notifications should be delivered to individual editors when approved or rejected.
- `editorNotificationsOptions` - Whether editor notifications should include the reviewer's or publisher's email whose triggered the action.
- `reviewerNotifications` - Whether email notifications should be delivered to reviewers when editors submit an entry for review.
- `reviewerApprovalNotifications` - Whether email notifications should be delivered to editors when each reviewer approves an entry after review.
- `publisherNotifications` - Whether email notifications should be delivered to publishers when editors submit an entry for review.
- `publishedAuthorNotifications` - Whether email notifications should be delivered to the entry author when approved and published by a Publisher.
- `selectedPublishers` - An array of user IDs of publishers to receive email notifications. Use '\*' for all.

Permissions
- `enabledSections` - An array of section UIDs to enable submissions on. Use '\*' for all.

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
    ]
];
```

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Workflow.
