<?php

use craft\db\Query;
use craft\db\Table;
use verbb\workflow\migrations\m261102_000000_sandbox_system_messages;

it('rewrites saved system messages the Twig sandbox would block', function() {
    $db = Craft::$app->getDb();
    $rows = [
        'workflow_publisher_notification' => [
            '"{{ submission.ownerTitle }}" went to review on {{ submission.getOwnerSite() }}.',
            "Hi, {{review.user}} and {{ submission.editor }} sent this on {{ submission.ownerSite }}.\n{{ submission.ownerSite.handle }}",
        ],
        'workflow_editor_notification' => ['Already fine on {{ submission.ownerSite.name }}.', 'Custom wording only.'],
        'unrelated_message' => ['{{ submission.ownerSite }}', '{{ review.user }}'],
    ];

    foreach ($rows as $key => [$subject, $body]) {
        $db->createCommand()->insert(Table::SYSTEMMESSAGES, ['language' => 'en-test', 'key' => $key, 'subject' => $subject, 'body' => $body])->execute();
    }

    $read = fn(string $key) => (new Query())->select(['subject', 'body'])->from(Table::SYSTEMMESSAGES)->where(['key' => $key, 'language' => 'en-test'])->one();

    try {
        $migration = new m261102_000000_sandbox_system_messages(['compact' => true]);
        expect($migration->safeUp())->toBeTrue();

        $expected = [
            'subject' => '"{{ submission.ownerTitle }}" went to review on {{ submission.ownerSite.name }}.',
            'body' => "Hi, {{ review.user.name }} and {{ submission.editor.name }} sent this on {{ submission.ownerSite.name }}.\n{{ submission.ownerSite.handle }}",
        ];
        expect($read('workflow_publisher_notification'))->toBe($expected);
        expect($read('workflow_editor_notification'))->toBe(['subject' => 'Already fine on {{ submission.ownerSite.name }}.', 'body' => 'Custom wording only.']);
        expect($read('unrelated_message'))->toBe(['subject' => '{{ submission.ownerSite }}', 'body' => '{{ review.user }}']);

        // A repeat run must leave rewritten messages alone.
        $migration->safeUp();
        expect($read('workflow_publisher_notification'))->toBe($expected);
    } finally {
        $db->createCommand()->delete(Table::SYSTEMMESSAGES, ['language' => 'en-test'])->execute();
    }
});
