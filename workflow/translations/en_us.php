<?php

return array (
    'workflow_publisher_notification_heading' => 'When an editor submits entry for approval:',
    'workflow_publisher_notification_subject' => '"{{ submission.owner.title }}" has been submitted for approval on {{ siteName }}.',
    'workflow_publisher_notification_body' => "Hey {{ user.friendlyName }},\n\n" .
        "{{ submission.editor }} has submitted the entry \"{{ submission.owner.title }}\" for approval on {{ siteName }}.\n\n" .
        "To review it please log into your control panel.\n\n" .
        "{{ submission.owner.cpEditUrl }}",
);