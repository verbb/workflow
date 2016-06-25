<?php

return array (
    'workflow_publisher_notification_heading' => 'When an editor submits entry for approval:',
    'workflow_publisher_notification_subject' => '"{{ element.title }}" has been submitted for approval on {{ siteName }}.',
    'workflow_publisher_notification_body' => "Hey {{ user.friendlyName }},\n\n" .
        "{{ sender.fullName }} has submitted the entry \"{{ element.title }}\" for approval on {{ siteName }}.\n\n" .
        "To review it please log into your control panel.\n\n" .
        "{{ element.cpEditUrl }}",
);