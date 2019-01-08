<?php

return [
    //
    // Email Messages
    //
    
    'workflow_publisher_notification_heading' => 'Når en skribent sender et utkast til godkjenning:',
    'workflow_publisher_notification_subject' => '"{{ submission.owner.title }}" har blitt sendt inn for godkjenning på {{ siteName }}.',
    'workflow_publisher_notification_body' => "Hei {{ user.friendlyName }},\n\n" .
        "{{ submission.editor }} har sendt utkastet \"{{ submission.owner.title }}\" til godkjenning på {{ siteName }}.\n\n" .
        "For å gå gjennom det, vennligst logg inn i kontrollpanelet.\n\n" .
        "{{ submission.cpEditUrl }}",

    'workflow_editor_notification_heading' => 'Når en redaktør godkjenner eller avslår et utkast fra en skribent:',
    'workflow_editor_notification_subject' => 'Ditt utkast til "{{ submission.owner.title }}" har blitt {{ submission.status }} på {{ siteName }}.',
    'workflow_editor_notification_body' => "Hei {{ user.friendlyName }},\n\n" .
        "Ditt utkast til {{ submission.owner.title }} har blitt {{ submission.status }} {{ (submission.status == 'approved') ? submission.dateApproved | date : submission.dateRejected | date }} på {{ siteName }}.\n\n" .
        "{% if submission.notes %}Merknad: \"{{ submission.notes }}\"\n\n{% endif %}" .
        "Se ditt utkast ved å logge inn i kontrollpanelet.\n\n" .
        "{{ submission.cpEditUrl }}",
];
