<?php
namespace verbb\workflow;

use verbb\workflow\base\PluginTrait;
use verbb\workflow\models\Settings;
use verbb\workflow\variables\WorkflowVariable;
use verbb\workflow\widgets\Submissions as SubmissionsWidget;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\SystemMessages;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\web\User;

class Workflow extends Plugin
{
    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();

        // Register our CP routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        // Register Widgets
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = SubmissionsWidget::class;
        });

        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, [$this, 'registerEmailMessages']);

        Craft::$app->view->hook('cp.entries.edit.details', [$this->getService(), 'renderEntrySidebar']);

        // Setup Variables class (for backwards compatibility)
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('workflow', WorkflowVariable::class);
        });
    }

    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $rules = [
            'workflow/drafts' => 'workflow/base/drafts',
            'workflow/settings' => 'workflow/base/settings',
        ];

        $event->rules = array_merge($event->rules, $rules);
    }

    public function registerEmailMessages(RegisterEmailMessagesEvent $event)
    {
        $messages = [
            [
                'key' => 'workflow_publisher_notification',
                'heading' => Craft::t('workflow', 'workflow_publisher_notification_heading'),
                'subject' => Craft::t('workflow', 'workflow_publisher_notification_subject'),
                'body' => Craft::t('workflow', 'workflow_publisher_notification_body'),
            ], [
                'key' => 'workflow_editor_notification',
                'heading' => Craft::t('workflow', 'workflow_editor_notification_heading'),
                'subject' => Craft::t('workflow', 'workflow_editor_notification_subject'),
                'body' => Craft::t('workflow', 'workflow_editor_notification_body'),
            ]
        ];

        $event->messages = array_merge($event->messages, $messages);
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('workflow/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }
}
