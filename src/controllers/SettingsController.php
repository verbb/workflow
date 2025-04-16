<?php
namespace verbb\workflow\controllers;

use verbb\workflow\Workflow;

use Craft;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $settings = Workflow::$plugin->getSettings();

        return $this->renderTemplate('workflow/settings/general', [
            'settings' => $settings,
        ]);
    }

    public function actionNotifications(): Response
    {
        $settings = Workflow::$plugin->getSettings();

        $userGroups = [
            ['label' => Craft::t('workflow', 'All Publishers'), 'value' => ''],
        ];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $userGroup) {
            $userGroups[] = ['label' => $userGroup->name, 'value' => $userGroup->uid];
        }

        $userGroups = array_unique($userGroups, SORT_REGULAR);

        return $this->renderTemplate('workflow/settings/notifications', [
            'settings' => $settings,
            'userGroups' => $userGroups,
        ]);
    }

    public function actionPermissions(): Response
    {
        $settings = Workflow::$plugin->getSettings();

        return $this->renderTemplate('workflow/settings/permissions', [
            'settings' => $settings,
        ]);
    }
}
