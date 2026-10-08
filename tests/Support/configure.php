<?php

// Request-local settings exercise production services without leaking configuration into another test.
$context = $input['context'] ?? [];
$settings = $p->getSettings();
$siteUid = $app->getSites()->getCurrentSite()->uid;
foreach (['lockDraftSubmissions', 'enabledSections', 'editorNotificationsOptions', 'reviewerApprovalNotifications'] as $key) {
    if (array_key_exists($key, $context)) {
        $settings->$key = $context[$key];
    }
}
foreach (['editorNotesRequired', 'publisherNotesRequired'] as $key) {
    if (array_key_exists($key, $context)) {
        $settings->$key = [$siteUid => $context[$key]];
    }
}
if (array_key_exists('reviewStages', $context)) {
    $settings->reviewerUserGroups[$siteUid] = array_map(fn($handle) => [$f['groups'][$handle]], array_slice(['reviewersOne', 'reviewersTwo'], 0, $context['reviewStages']));
}
if (isset($context['reviewGroups'])) {
    $settings->reviewerUserGroups[$siteUid] = array_map(fn($handle) => [$f['groups'][$handle]], $context['reviewGroups']);
}
if ($context['separateSitePublisher'] ?? false) {
    $secondary = $app->getSites()->getSiteById($f['sites'][1]);
    $settings->publisherUserGroup[$secondary->uid] = $f['groups']['secondaryPublishers'];
}
if ($context['selfApprovalGroup'] ?? false) {
    $settings->publisherSelfApprovalUserGroups[$siteUid] = [[$f['groups']['publishers']]];
}
if ($context['selfApprovalEvent'] ?? false) {
    $p->getSubmissions()->on(verbb\workflow\services\Submissions::EVENT_DEFINE_PUBLISHER_SELF_APPROVAL, fn($event) => $event->allowSelfApproval = true);
}
if ($context['approveOnly'] ?? false) {
    $p->getActions()->on(verbb\workflow\services\Actions::EVENT_REGISTER_PUBLISHER_ACTIONS, function($event) {
        $event->actions[] = verbb\workflow\actions\ApproveOnlySubmission::class;
    });
}
if ($context['failReviewSave'] ?? false) {
    $p->getReviews()->on(verbb\workflow\services\Reviews::EVENT_BEFORE_SAVE_REVIEW, function($event) {
        throw new RuntimeException('Injected review persistence failure');
    });
}
// All messages use the real Craft mail composition, with delivery restricted to local files.
$mailer = $app->getMailer();
$mailer->useFileTransport = true;
$mailer->fileTransportPath = CRAFT_STORAGE_PATH . '/test-mail';
$mailer->on(yii\mail\BaseMailer::EVENT_AFTER_SEND, function($event) use (&$out) {
    $out['mail'][] = ['to' => $event->message->getTo(), 'cc' => $event->message->getCc(), 'replyTo' => $event->message->getReplyTo(), 'subject' => $event->message->getSubject(), 'sent' => $event->isSuccessful];
});
if ($context['notifications'] ?? false) {
    foreach (['editorNotifications', 'reviewerNotifications', 'publisherNotifications', 'publishedAuthorNotifications'] as $key) {
        $settings->$key = ($context['notifications'][$key] ?? true);
    }
}
if ($context['cancelMail'] ?? false) {
    foreach (['beforeSendEditorEmail', 'beforeSendReviewerEmail', 'beforeSendPublisherEmail'] as $eventName) {
        $p->getEmails()->on($eventName, fn($event) => $event->isValid = false);
    }
}

if (array_key_exists('versioning', $context)) {
    $app->getEntries()->getSectionById($f['sectionId'])->enableVersioning = $context['versioning'];
}
if (array_key_exists('persistNotesRequirement', $input)) {
    $settings->publisherNotesRequired[$siteUid] = $input['persistNotesRequirement'];
    if (!$app->getPlugins()->savePluginSettings($p, $settings->toArray())) {
        throw new RuntimeException('Cannot configure browser notes fixture.');
    }
    $app->getProjectConfig()->saveModifiedConfigData();
}
