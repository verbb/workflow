<?php
namespace verbb\workflow\migrations;

use verbb\workflow\Workflow;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\services\ProjectConfig;

class m190222_000000_migrate_settings_to_uid extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
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

        $users = (new Query())
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

        if ($settings->editorUserGroup && isset($userGroups[$settings->editorUserGroup])) {
            $settings->editorUserGroup = $userGroups[$settings->editorUserGroup];
        }

        if ($settings->publisherUserGroup && isset($userGroups[$settings->publisherUserGroup])) {
            $settings->publisherUserGroup = $userGroups[$settings->publisherUserGroup];
        }

        // Update the plugin's settings in the project config
        Craft::$app->getProjectConfig()->set(ProjectConfig::PATH_PLUGINS . '.' . $plugin->handle . '.settings', $settings->toArray());

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190222_000000_migrate_settings_to_uid cannot be reverted.\n";
        return false;
    }
}
