# Configuration

Create an `workflow.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        // General
        'editorUserGroup' => '',
        'reviewerUserGroups' => [],
        'publisherUserGroup' => '',
        'editorNotesRequired' => false,
        'publisherNotesRequired' => false,
        'lockDraftSubmissions' => true,

        // Notifications
        'editorNotifications' => true,
        'editorNotificationsOptions' => [],
        'reviewerNotifications' => true,
        'reviewerApprovalNotifications' => false,
        'publisherNotifications' => true,
        'selectedPublishers' => '*',

        // Permissions
        'enabledSections' => '*',
    ]
];
```

### Configuration options

General
- `editorUserGroup` - The User Group UID for editors.
- `reviewerUserGroups` - A collection of user groups for each reviewer.
- `publisherUserGroup` - The User Group UID for publishers.
- `editorNotesRequired` - Whether editors are required to enter a note in their submissions.
- `publisherNotesRequired` - Whether publishers are required to enter a note in their submissions.
- `lockDraftSubmissions` - Whether an entry should be locked for editing after it‘s been submitted for review.

Notifications
- `editorNotifications` - Whether email notifications should be delivered to individual editors when approved or rejected.
- `editorNotificationsOptions` - Whether editor notifications should include the reviewer's or publisher's email whose triggered the action.
- `reviewerNotifications` - Whether email notifications should be delivered to reviewers when editors submit an entry for review.
- `reviewerApprovalNotifications` - Whether email notifications should be delivered to editors when each reviewer approves an entry after review.
- `publisherNotifications` - Whether email notifications should be delivered to publishers when editors submit an entry for review.
- `selectedPublishers` - An array of user IDs of publishers to receive email notifications. Use '\*' for all.

Permissions
- `enabledSections` - An array of section UIDs to enable submissions on. Use '\*' for all.


## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Workflow.
