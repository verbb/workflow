<?php
namespace verbb\workflow\controllers;

use Craft;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Db;
use craft\web\Controller;

use verbb\workflow\Workflow;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = Workflow::$plugin->getSettings();

        $publishers = [];

        if ($settings->publisherUserGroup) {
            $publisherUserGroup = $settings->publisherUserGroup;

            // Backward-compatibility support for config files, which won't be migrated
            if (!is_array($publisherUserGroup)) {
                Craft::$app->getDeprecator()->log('publisherUserGroup', 'The `publisherUserGroup` setting has been updated, and will cause a fatal error in Craft 4. Please review our [docs](https://verbb.io/craft-plugins/workflow/docs/get-started/configuration).');
                
                $publisherUserGroupUid = $publisherUserGroup;
                $publisherUserGroup = [];
                
                foreach (Craft::$app->getSites()->getAllSites() as $site) {
                    $publisherUserGroup[$site->uid] = $publisherUserGroupUid;
                }
            }

            foreach ($publisherUserGroup as $siteUid => $publisherUserGroupUid) {
                $publisherUserGroupId = Db::idByUid(Table::USERGROUPS, $publisherUserGroupUid);

                foreach (User::find()->groupId($publisherUserGroupId)->all() as $user) {
                    $publishers[] = ['value' => $user->id, 'label' => (string)$user];
                }
            }
        }

        $publishers = array_unique($publishers, SORT_REGULAR);

        return $this->renderTemplate('workflow/settings', [
            'settings' => $settings,
            'publishers' => $publishers,
        ]);
    }

    public function actionSavePluginSettings()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser->can('workflow:settings')) {
            throw new ForbiddenHttpException('User is not permitted to perform this action.');
        }

        $this->requirePostRequest();

        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', "Couldn't save plugin settings."));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
