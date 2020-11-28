# Configuration

Create an `workflow.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'enabledSections' => '*',
        'editorUserGroup' => '',
        'publisherUserGroup' => '',
        'editorNotifications' => true,
        'publisherNotifications' => true,
        'selectedPublishers' => '*',
        'lockDraftSubmissions' => true,
    ]
];
```

### Configuration options

- `enabledSections` - An array of section IDs to enable submissions on. Use '\*' for all.
- `editorUserGroup` - The User Group ID for editors.
- `publisherUserGroup` - The User Group ID for publishers.
- `editorNotifications` - Whether editors should receive email notifications.
- `publisherNotifications` - Whether publishers should receive email notifications.
- `selectedPublishers` - An array of user IDs of publishers to receive email notifications. Use '\*' for all.
- `lockDraftSubmissions` - Whether an entry should be locked for editing after it‘s been submitted for review.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Workflow.
