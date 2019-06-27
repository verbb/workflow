<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\queue\jobs\ResaveElements;

class m190627_000000_resaveSubmissions extends Migration
{
    public function safeUp()
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Submission::class,
        ]));
    }

    public function safeDown()
    {
        echo "m190627_000000_resaveSubmissions cannot be reverted.\n";

        return false;
    }
}
