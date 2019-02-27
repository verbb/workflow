<?php
namespace verbb\workflow\controllers;

use Craft;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
use craft\web\Controller;

use verbb\workflow\Workflow;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionDrafts()
    {
        $drafts = Workflow::$plugin->getDrafts()->getAllDrafts();

        return $this->renderTemplate('workflow/drafts', [
            'entries' => $drafts,
        ]);
    }

    public function actionSettings()
    {
        $settings = Workflow::$plugin->getSettings();

        $publishers = [];

        if ($settings->publisherUserGroup) {
            $publisherUserGroupId = Db::idByUid(Table::USERGROUPS, $settings->publisherUserGroup);

            foreach (User::find()->groupId($publisherUserGroupId)->all() as $user) {
                $publishers[] = ['value' => $user->id, 'label' => $user];
            }
        }

        return $this->renderTemplate('workflow/settings', [
            'settings' => $settings,
            'publishers' => $publishers,
        ]);
    }
}
