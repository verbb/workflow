<?php

// Persist fixture changes between real requests, returning enough state for a finally-block restore.
if (isset($input['mutateUsers'])) {
    foreach ($input['mutateUsers'] as $name => $changes) {
        $actor = craft\elements\User::find()->id($f['users'][$name])->status(null)->one();
        $out['restoreUsers'][$name] = ['groups' => array_map(fn($group) => $group->handle, $actor->getGroups()), 'suspended' => $actor->suspended];
        if (isset($changes['groups'])) {
            $ids = array_map(fn($handle) => $app->getUserGroups()->getGroupByHandle($handle)->id, $changes['groups']);
            $app->getUsers()->assignUserToGroups($actor->id, $ids);
        }
        if (isset($changes['suspended'])) {
            if ($changes['suspended']) {
                $app->getUsers()->suspendUser($actor);
            } else {
                $app->getUsers()->unsuspendUser($actor);
            }
        }
    }
}
if (isset($input['mutatePermissions'])) {
    foreach ($input['mutatePermissions'] as $handle => $changes) {
        $group = $app->getUserGroups()->getGroupByHandle($handle);
        $permissions = $app->getUserPermissions()->getPermissionsByGroupId($group->id);
        $out['restorePermissions'][$handle] = ['set' => $permissions];
        if (isset($changes['remove'])) {
            $permissions = array_values(array_filter($permissions, fn($permission) => !in_array(strtolower(explode(':', $permission)[0]), array_map('strtolower', $changes['remove']))));
        } else {
            $permissions = $changes['set'];
        }
        if (!$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions)) {
            throw new RuntimeException('Cannot update fixture permissions.');
        }
    }
}

if (isset($input['mutatePermissions'])) {
    $app->getProjectConfig()->saveModifiedConfigData();
}
