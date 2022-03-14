<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;
use craft\helpers\ProjectConfig;
use craft\services\Plugins;

class m220228_000000_multisite_settings extends Migration
{
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.workflow.schemaVersion', true);
        
        if (version_compare($schemaVersion, '2.1.6', '>=')) {
            return;
        }

        $settings = Workflow::$plugin->getSettings();

        $newSettings = [
            'editorUserGroup',
            'publisherUserGroup',
            'reviewerUserGroups',
            'editorNotesRequired',
            'publisherNotesRequired',
        ];

        // Convert all non-multi-site config settings to their config setting.
        foreach ($newSettings as $newSetting) {
            $primarySite = Craft::$app->getSites()->getPrimarySite()->uid;

            if (!is_array($settings->$newSetting) || !isset($settings->$newSetting[$primarySite])) {
                $tempSetting = $settings->$newSetting;
                $settings->$newSetting = [];

                foreach (Craft::$app->getSites()->getAllSites() as $site) {
                    $settings->$newSetting[$site->uid] = $tempSetting;
                }
            }
        }

        $settings = $settings->toArray();

        // Update the plugin's settings in the project config
        $settings = ProjectConfig::packAssociativeArrays($settings);

        Craft::$app->getProjectConfig()->set(Plugins::CONFIG_PLUGINS_KEY . '.workflow.settings', $settings);
    }

    public function safeDown()
    {
        echo "m220228_000000_multisite_settings cannot be reverted.\n";

        return false;
    }
}
