<?php
namespace verbb\workflow;

use verbb\workflow\base\PluginTrait;
use verbb\workflow\elements\Submission;
use verbb\workflow\models\Settings;
use verbb\workflow\variables\WorkflowVariable;
use verbb\workflow\widgets\Submissions as SubmissionsWidget;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

/**
 * @method Settings getSettings()
 */
class Workflow extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '2.1.6';
    public string $minVersionRequired = '1.6.6';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerEmailMessages();
        $this->_registerWidgets();
        $this->_registerVariables();
        $this->_registerCraftEventListeners();
        $this->_registerElementTypes();
        $this->_registerPermissions();
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('workflow/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event): void {
            $event->rules = array_merge($event->rules, [
                'workflow/settings' => 'workflow/base/settings',
            ]);
        });
    }

    private function _registerEmailMessages(): void
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event): void {
            $event->messages = array_merge($event->messages, [
                [
                    'key' => 'workflow_publisher_notification',
                    'heading' => Craft::t('workflow', 'workflow_publisher_notification_heading'),
                    'subject' => Craft::t('workflow', 'workflow_publisher_notification_subject'),
                    'body' => Craft::t('workflow', 'workflow_publisher_notification_body'),
                ], [
                    'key' => 'workflow_editor_review_notification',
                    'heading' => Craft::t('workflow', 'workflow_editor_review_notification_heading'),
                    'subject' => Craft::t('workflow', 'workflow_editor_review_notification_subject'),
                    'body' => Craft::t('workflow', 'workflow_editor_review_notification_body'),
                ], [
                    'key' => 'workflow_editor_notification',
                    'heading' => Craft::t('workflow', 'workflow_editor_notification_heading'),
                    'subject' => Craft::t('workflow', 'workflow_editor_notification_subject'),
                    'body' => Craft::t('workflow', 'workflow_editor_notification_body'),
                ],
            ]);
        });
    }

    private function _registerWidgets(): void
    {
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = SubmissionsWidget::class;
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event): void {
            $event->sender->set('workflow', WorkflowVariable::class);
        });
    }

    private function _registerElementTypes(): void
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = Submission::class;
        });
    }

    private function _registerCraftEventListeners(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Event::on(Entry::class, Entry::EVENT_BEFORE_SAVE, [$this->getService(), 'onBeforeSaveEntry']);
        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, [$this->getService(), 'onAfterSaveEntry']);

        Event::on(Drafts::class, Drafts::EVENT_AFTER_APPLY_DRAFT, [$this->getService(), 'onAfterApplyDraft']);

        Event::on(Entry::class, Entry::EVENT_DEFINE_SIDEBAR_HTML, [$this->getService(), 'renderEntrySidebar']);
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event): void {
            $event->permissions[] = [
                'heading' => Craft::t('workflow', 'Workflow'),
                'permissions' => [
                    'workflow-overview' => ['label' => Craft::t('workflow', 'Overview')],
                    'workflow-settings' => ['label' => Craft::t('workflow', 'Settings')],
                ],
            ];
        });
    }
}
