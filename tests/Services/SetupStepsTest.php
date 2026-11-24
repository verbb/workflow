<?php

use craft\db\Query;
use craft\helpers\Sequence;
use verbb\workflow\Workflow;
use verbb\workflow\services\Submissions;

it('reports each role across sites until the first submission', function() {
    $db = Craft::$app->getDb();
    $transaction = $db->beginTransaction();

    try {
        // Simulate a site that has never had a submission, whatever earlier tests left behind.
        $db->createCommand()->delete('{{%workflow_submissions}}')->execute();
        $db->createCommand()->delete('{{%sequences}}', ['name' => Submissions::FIRST_SUBMISSION_SEQUENCE])->execute();

        $steps = Workflow::$plugin->getSubmissions()->getSetupSteps();

        expect($steps['siteCount'])->toBe(2);
        expect($steps['hasUserGroups'])->toBeTrue();
        expect($steps['editor'])->toBe(['done' => true, 'siteCount' => 2, 'groupNames' => ['Editors']]);
        expect($steps['publisher'])->toBe(['done' => true, 'siteCount' => 2, 'groupNames' => ['Publishers']]);
    } finally {
        $transaction->rollBack();
    }
});

it('keeps the setup checklist away after the first submission is deleted', function() {
    $fixture = workflowSubmission('draft');
    $submissionId = $fixture['submission']['id'];

    expect((int)Sequence::current(Submissions::FIRST_SUBMISSION_SEQUENCE))->toBeGreaterThan(0);

    $db = Craft::$app->getDb();
    $transaction = $db->beginTransaction();

    try {
        // Garbage collection removes trashed submissions for good, which leaves no rows behind.
        $db->createCommand()->delete('{{%workflow_submissions}}')->execute();
        expect((new Query())->from('{{%workflow_submissions}}')->exists())->toBeFalse();

        expect(Workflow::$plugin->getSubmissions()->getSetupSteps())->toBeNull();
    } finally {
        $transaction->rollBack();
    }

    expect((new Query())->from('{{%workflow_submissions}}')->where(['id' => $submissionId])->exists())->toBeTrue();
});

it('never shows the setup checklist on installs made before it was added', function() {
    $plugins = Craft::$app->getPlugins();

    // Craft keeps the stored plugin rows in memory, so the install date is swapped there rather than in the database.
    $setInstallDate = Closure::bind(function(?DateTime $date) {
        $previous = $this->_storedPluginInfo['workflow']['installDate'];
        $this->_storedPluginInfo['workflow']['installDate'] = $date;

        return $previous;
    }, $plugins, $plugins::class);

    $db = Craft::$app->getDb();
    $transaction = $db->beginTransaction();
    $installDate = $setInstallDate(new DateTime('2026-11-22 23:59:59', new DateTimeZone('UTC')));

    try {
        $db->createCommand()->delete('{{%workflow_submissions}}')->execute();
        $db->createCommand()->delete('{{%sequences}}', ['name' => Submissions::FIRST_SUBMISSION_SEQUENCE])->execute();

        expect(Workflow::$plugin->getSubmissions()->getSetupSteps())->toBeNull();

        $setInstallDate(null);
        expect(Workflow::$plugin->getSubmissions()->getSetupSteps())->toBeNull();

        $setInstallDate(new DateTime(Submissions::SETUP_CHECKLIST_SINCE, new DateTimeZone('UTC')));
        expect(Workflow::$plugin->getSubmissions()->getSetupSteps())->toBeArray();
    } finally {
        $setInstallDate($installDate);
        $transaction->rollBack();
    }
});
