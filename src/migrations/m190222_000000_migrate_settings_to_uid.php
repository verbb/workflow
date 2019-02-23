<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Plugins;

class m190222_000000_migrate_settings_to_uid extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $plugin = Workflow::$plugin;
        $settings = Workflow::$plugin->getSettings();

        $sections = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::SECTIONS])
            ->pairs();

        $userGroups = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::USERGROUPS])
            ->pairs();

        $userss = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::USERS])
            ->pairs();

        if (is_array($settings->enabledSections)) {
            foreach ($settings->enabledSections as $key => $sectionId) {
                if (isset($sections[$sectionId])) {
                    $settings->enabledSections[$key] = $sections[$sectionId];
                }
            }
        }

        if ($settings->editorUserGroup) {
            if (isset($userGroups[$settings->editorUserGroup])) {
                $settings->editorUserGroup = $userGroups[$settings->editorUserGroup];
            }
        }

        if ($settings->publisherUserGroup) {
            if (isset($userGroups[$settings->publisherUserGroup])) {
                $settings->publisherUserGroup = $userGroups[$settings->publisherUserGroup];
            }
        }

        // Update the plugin's settings in the project config
        Craft::$app->getProjectConfig()->set(Plugins::CONFIG_PLUGINS_KEY . '.' . $plugin->handle . '.settings', $settings->toArray());
    }

    public function safeDown()
    {
        echo "m190222_000000_migrate_settings_to_uid cannot be reverted.\n";
        return false;
    }
}
