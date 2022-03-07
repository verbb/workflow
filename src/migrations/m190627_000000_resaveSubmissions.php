<?php
namespace verbb\workflow\migrations;

use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;

class m190627_000000_resaveSubmissions extends Migration
{
    public function safeUp(): bool
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Submission::class,
        ]));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190627_000000_resaveSubmissions cannot be reverted.\n";

        return false;
    }
}
