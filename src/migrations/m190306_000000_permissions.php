<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\db\Query;

class m190306_000000_permissions extends Migration
{
    public function safeUp(): bool
    {
        $permissionIds = [];

        // Update permissions
        $this->insert('{{%userpermissions}}', ['name' => 'workflow-overview']);
        $permissionIds[] = $this->db->getLastInsertID('{{%userpermissions}}');

        $this->insert('{{%userpermissions}}', ['name' => 'workflow:drafts']);
        $permissionIds[] = $this->db->getLastInsertID('{{%userpermissions}}');

        $this->insert('{{%userpermissions}}', ['name' => 'workflow-settings']);
        $permissionIds[] = $this->db->getLastInsertID('{{%userpermissions}}');

        // See which users & groups already have the "accessplugin-workflow" permission
        $userIds = (new Query())
            ->select(['up_u.userId'])
            ->from(['{{%userpermissions_users}} up_u'])
            ->innerJoin('{{%userpermissions}} up', '[[up.id]] = [[up_u.permissionId]]')
            ->where(['up.name' => 'accessplugin-workflow'])
            ->column($this->db);

        $groupIds = (new Query())
            ->select(['up_ug.groupId'])
            ->from(['{{%userpermissions_usergroups}} up_ug'])
            ->innerJoin('{{%userpermissions}} up', '[[up.id]] = [[up_ug.permissionId]]')
            ->where(['up.name' => 'accessplugin-workflow'])
            ->column($this->db);

        if (empty($userIds) && empty($groupIds)) {
            return true;
        }

        // Assign the new permissions to the users
        if (!empty($userIds)) {
            $data = [];

            foreach ($userIds as $userId) {
                foreach ($permissionIds as $permissionId) {
                    $data[] = [$permissionId, $userId];
                }
            }

            $this->batchInsert('{{%userpermissions_users}}', ['permissionId', 'userId'], $data);
        }

        // Assign the new permissions to the groups
        if (!empty($groupIds)) {
            $data = [];

            foreach ($groupIds as $groupId) {
                foreach ($permissionIds as $permissionId) {
                    $data[] = [$permissionId, $groupId];
                }
            }

            $this->batchInsert('{{%userpermissions_usergroups}}', ['permissionId', 'groupId'], $data);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190306_000000_permissions cannot be reverted.\n";

        return false;
    }
}
