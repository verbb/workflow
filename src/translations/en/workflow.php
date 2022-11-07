<?php

return [
    //
    // Email Messages
    //

    'workflow_publisher_notification_heading' => 'When an editor submits entry for approval:',
    'workflow_publisher_notification_subject' => '"{{ submission.ownerTitle }}" has been submitted for approval on {{ submission.getOwnerSite() }}.',
    'workflow_publisher_notification_body' => "Hey {{ user.friendlyName }},\n\n" .
        "{{ review.user }} has submitted the entry \"{{ submission.ownerTitle }}\" for approval on {{ submission.ownerSite }}.\n\n" .
        "{% if review.notes %}{{ review.roleName }} Notes: \"{{ review.notes }}\"\n\n{% endif %}" .
        "To review it please log into your control panel.\n\n" .
        "{{ submission.owner.cpEditUrl }}",

    'workflow_editor_review_notification_heading' => 'When a reviewer approves or rejects an editor submission:',
    'workflow_editor_review_notification_subject' => 'Your submission for "{{ submission.ownerTitle }}" has been {{ review.status }} on {{ submission.ownerSite }}.',
    'workflow_editor_review_notification_body' => "Hey {{ user.friendlyName }},\n\n" .
        "Your submission for {{ submission.ownerTitle }} has been {{ review.status }} {{ review.dateCreated | date }} on {{ submission.ownerSite }}.\n\n" .
        "{% if review.notes %}{{ review.roleName }} Notes: \"{{ review.notes }}\"\n\n{% endif %}" .
        "View your submission by logging into your control panel.\n\n" .
        "{{ submission.owner.cpEditUrl }}",

    'workflow_editor_notification_heading' => 'When a publisher approves or rejects an editor submission:',
    'workflow_editor_notification_subject' => 'Your submission for "{{ submission.ownerTitle }}" has been {{ review.status }} on {{ submission.ownerSite }}.',
    'workflow_editor_notification_body' => "Hey {{ user.friendlyName }},\n\n" .
        "Your submission for {{ submission.ownerTitle }} has been {{ review.status }} {{ review.dateCreated | date }} on {{ submission.ownerSite }}.\n\n" .
        "{% if review.notes %}{{ review.roleName }} Notes: \"{{ review.notes }}\"\n\n{% endif %}" .
        "View your submission by logging into your control panel.\n\n" .
        "{{ submission.owner.cpEditUrl }}",

    'workflow_published_author_notification_heading' => 'When a publisher approves and publishes an entry to notify the entry author:',
    'workflow_published_author_notification_subject' => 'Your entry "{{ submission.ownerTitle }}" has been published on {{ submission.ownerSite }}.',
    'workflow_published_author_notification_body' => "Hey {{ user.friendlyName }},\n\n" .
        "Your entry {{ submission.ownerTitle }} has been published {{ review.dateCreated | date }} on {{ submission.ownerSite }}.\n\n" .
        "View your entry by logging into your control panel.\n\n" .
        "{{ submission.owner.cpEditUrl }}",

    'Workflow Submission' => 'Workflow Submission',
	'Approved' => 'Approved',
	'Pending' => 'Pending',
	'Rejected' => 'Rejected',
	'Revoked' => 'Revoked',
	'All submissions' => 'All submissions',
	'Are you sure you want to delete the selected submissions?' => 'Are you sure you want to delete the selected submissions?',
	'Submissions deleted.' => 'Submissions deleted.',
	'Entry' => 'Entry',
	'Site' => 'Site',
	'Date Submitted' => 'Date Submitted',
	'Editor' => 'Editor',
	'Last Reviewed' => 'Last Reviewed',
	'Last Reviewed By' => 'Last Reviewed By',
	'Publisher' => 'Publisher',
	'Editor Notes' => 'Editor Notes',
	'Publisher Notes' => 'Publisher Notes',
	'Date Approved' => 'Date Approved',
	'Date Rejected' => 'Date Rejected',
	'[Deleted element' => '[Deleted element]',
	'Set Status' => 'Set Status',
	'Could not update status due to a validation error.' => 'Could not update status due to a validation error.',
	'Could not update statuses due to validation errors.' => 'Could not update statuses due to validation errors.',
	'Status updated, with some failures due to validation errors.' => 'Status updated, with some failures due to validation errors.',
	'Status updated.' => 'Status updated.',
	'Statuses updated.' => 'Statuses updated.',
	'Reviewer Notes' => 'Reviewer Notes',
	'Awaiting approval' => 'Awaiting approval',
	'submitted this entry for review on {date}. Please review this entry before publishing.' => 'submitted this entry for review on {date}. Please review this entry before publishing.',
	'Approve and publish' => 'Approve and publish',
	'Approve only' => 'Approve only',
	'Reject' => 'Reject',
	'Approved submission' => 'Approved submission',
	'approved this entry on {date}.' => 'approved this entry on {date}.',
	'Rejected submission' => 'Rejected submission',
	'rejected this entry on {date}.' => 'rejected this entry on {date}.',
	'Revoked submission' => 'Revoked submission',
	'revoked this submission on {date}.' => 'revoked this submission on {date}.',
	'Notes about your response.' => 'Notes about your response.',
	'Awaiting review' => 'Awaiting review',
	'submitted this entry for review on {date}. Please review this entry before approving.' => 'submitted this entry for review on {date}. Please review this entry before approving.',
	'Approve' => 'Approve',
	'This entry was submitted for review on {date} and is awaiting approval. Changes cannot be made until approved.' => 'This entry was submitted for review on {date} and is awaiting approval. Changes cannot be made until approved.',
	'Submit for review' => 'Submit for review',
	'Submitting this entry for review will lock further edits and notify the next editors in the approval process that this entry is ready for approval.' => 'Submitting this entry for review will lock further edits and notify the next editors in the approval process that this entry is ready for approval.',
	'Save draft and submit for review' => 'Save draft and submit for review',
	'Revoke submission' => 'Revoke submission',
	'revoked this entry on {date}.' => 'revoked this entry on {date}.',
	'Workflow' => 'Workflow',
	'Settings' => 'Settings',
	'General Settings' => 'General Settings',
	'Notifications' => 'Notifications',
	'Permissions' => 'Permissions',
	'Editor Notifications' => 'Editor Notifications',
	'Whether email notifications should be delivered to individual editors when approved or rejected.' => 'Whether email notifications should be delivered to individual editors when approved or rejected.',
	'Editor Notifications - Additional Options' => 'Editor Notifications - Additional Options',
	'Whether editor notifications should include the reviewer\\‘s or publisher\\‘s email whose triggered the action.' => 'Whether editor notifications should include the reviewer\\‘s or publisher\\‘s email whose triggered the action.',
	'Reply-To Reviewer Email' => 'Reply-To Reviewer Email',
	'CC Reviewer Email' => 'CC Reviewer Email',
	'Reply-To Publisher Email' => 'Reply-To Publisher Email',
	'CC Publisher Email' => 'CC Publisher Email',
	'Reviewer Notifications' => 'Reviewer Notifications',
	'Whether email notifications should be delivered to reviewers when editors submit an entry for review.' => 'Whether email notifications should be delivered to reviewers when editors submit an entry for review.',
	'Reviewer Approval Notifications' => 'Reviewer Approval Notifications',
	'Whether email notifications should be delivered to editors when each reviewer approves an entry after review.' => 'Whether email notifications should be delivered to editors when each reviewer approves an entry after review.',
	'Publisher Notifications' => 'Publisher Notifications',
	'Whether email notifications should be delivered to publishers when editors submit an entry for review.' => 'Whether email notifications should be delivered to publishers when editors submit an entry for review.',
	'Publishers To Receive Notifications' => 'Publishers To Receive Notifications',
	'Select all, or specific publishers to receive email notifications. By default, all will be notified.' => 'Select all, or specific publishers to receive email notifications. By default, all will be notified.',
	'Select a Publisher User Group first.' => 'Select a Publisher User Group first.',
	'None' => 'None',
	'Editor User Group' => 'Editor User Group',
	'Select the user group that your editors belong to. Editors are users that can edit content, but not publish live.' => 'Select the user group that your editors belong to. Editors are users that can edit content, but not publish live.',
	'Editor Submission Notes Required' => 'Editor Submission Notes Required',
	'Whether editors are required to enter a note in their submissions.' => 'Whether editors are required to enter a note in their submissions.',
	'User Group' => 'User Group',
	'Reviewer User Groups' => 'Reviewer User Groups',
	'Select the user groups that your reviewers belong to. Reviewers are users that can review and edit submissions and pass them along for approval, but not publish live. The review process flows from the first to the last user group in the table.' => 'Select the user groups that your reviewers belong to. Reviewers are users that can review and edit submissions and pass them along for approval, but not publish live. The review process flows from the first to the last user group in the table.',
	'Add a user group' => 'Add a user group',
	'Changing this may result in submissions being lost in the review process.' => 'Changing this may result in submissions being lost in the review process.',
	'Publisher User Group' => 'Publisher User Group',
	'Select the user group that your publishers belong to. Publishers are users who are notified when an editor submits content for review, and can approve content to be published live.' => 'Select the user group that your publishers belong to. Publishers are users who are notified when an editor submits content for review, and can approve content to be published live.',
	'Publisher Submission Notes Required' => 'Publisher Submission Notes Required',
	'Whether publishers are required to enter a note in their submissions.' => 'Whether publishers are required to enter a note in their submissions.',
	'Lock Draft Submissions' => 'Lock Draft Submissions',
	'Whether an entry should be locked for editing after it‘s been submitted for review.' => 'Whether an entry should be locked for editing after it‘s been submitted for review.',
	'Enabled Sections' => 'Enabled Sections',
	'Select which sections the workflow should be enabled for.' => 'Select which sections the workflow should be enabled for.',
	'Overview' => 'Overview',
	'Set status' => 'Set status',
	'No submissions.' => 'No submissions.',
	'Status' => 'Status',
	'Show submissions for certain status.' => 'Show submissions for certain status.',
	'All' => 'All',
	'Limit' => 'Limit',
	'Editor notes are required' => 'Editor notes are required',
	'Publisher notes are required' => 'Publisher notes are required',
	'Plugin settings saved.' => 'Plugin settings saved.',
	'Unable to edit entry once it has been submitted for review.' => 'Unable to edit entry once it has been submitted for review.',
	'Could not submit for approval.' => 'Could not submit for approval.',
	'Entry submitted for approval.' => 'Entry submitted for approval.',
	'Could not revoke submission.' => 'Could not revoke submission.',
	'Submission revoked.' => 'Submission revoked.',
	'Could not approve submission.' => 'Could not approve submission.',
	'Submission approved.' => 'Submission approved.',
	'Could not reject submission.' => 'Could not reject submission.',
	'Submission rejected.' => 'Submission rejected.',
	'Could not approve and publish.' => 'Could not approve and publish.',
	'Entry approved and published.' => 'Entry approved and published.',
	'No submission with the ID “{id}”' => 'No submission with the ID “{id}”',
	'Workflow Submissions' => 'Workflow Submissions',
];