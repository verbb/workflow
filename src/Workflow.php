<?php
namespace verbb\workflow;

use verbb\workflow\base\PluginTrait;
use verbb\workflow\elements\Submission;
use verbb\workflow\gql\interfaces\SubmissionInterface;
use verbb\workflow\gql\queries\SubmissionQuery;
use verbb\workflow\models\Settings;
use verbb\workflow\variables\WorkflowVariable;
use verbb\workflow\widgets\Submissions as SubmissionsWidget;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\console\Controller as ConsoleController;
use craft\console\controllers\ResaveController;
use craft\elements\Entry;
use craft\events\DefineConsoleActionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\Gql;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

class Workflow extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '2.4.0';
    public string $minVersionRequired = '1.7.0';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerComponents();
        $this->_registerLogTarget();
        $this->_registerEmailMessages();
        $this->_registerVariables();
        $this->_registerCraftEventListeners();
        $this->_registerElementTypes();
        $this->_registerGraphQl();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
            $this->_registerWidgets();
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->_registerResaveCommand();
        }
        
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
        }
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('workflow/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event): void {
            $event->rules = array_merge($event->rules, [
                'workflow/submissions/edit/<submissionId:\d+>' => 'workflow/submissions/edit',
                'workflow/reviews/compare/<newReviewId:\d+>:<oldReviewId:\d+>' => 'workflow/reviews/compare',
                'workflow/settings' => 'workflow/base/settings',
            ]);
        });
    }

    private function _registerEmailMessages(): void
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event): void {
            $event->messages[] = [
                'key' => 'workflow_publisher_notification',
                'heading' => Craft::t('workflow', 'workflow_publisher_notification_heading'),
                'subject' => Craft::t('workflow', 'workflow_publisher_notification_subject'),
                'body' => Craft::t('workflow', 'workflow_publisher_notification_body'),
            ];

            $event->messages[] = [
                'key' => 'workflow_editor_review_notification',
                'heading' => Craft::t('workflow', 'workflow_editor_review_notification_heading'),
                'subject' => Craft::t('workflow', 'workflow_editor_review_notification_subject'),
                'body' => Craft::t('workflow', 'workflow_editor_review_notification_body'),
            ];

            $event->messages[] = [
                'key' => 'workflow_editor_notification',
                'heading' => Craft::t('workflow', 'workflow_editor_notification_heading'),
                'subject' => Craft::t('workflow', 'workflow_editor_notification_subject'),
                'body' => Craft::t('workflow', 'workflow_editor_notification_body'),
            ];

            $event->messages[] = [
                'key' => 'workflow_published_author_notification',
                'heading' => Craft::t('workflow', 'workflow_published_author_notification_heading'),
                'subject' => Craft::t('workflow', 'workflow_published_author_notification_subject'),
                'body' => Craft::t('workflow', 'workflow_published_author_notification_body'),
            ];
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

        // Use `Entry::EVENT_BEFORE_SAVE` in order to add validation errors. `Elements::EVENT_BEFORE_SAVE_ELEMENT` doesn't work
        Event::on(Entry::class, Entry::EVENT_BEFORE_SAVE, [$this->getService(), 'onBeforeSaveEntry']);

        // Use `Elements::EVENT_AFTER_SAVE_ELEMENT` so that the element is fully finished with propagating, etc.
        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, [$this->getService(), 'onAfterSaveElement']);

        Event::on(Entry::class, Entry::EVENT_DEFINE_SIDEBAR_HTML, [$this->getService(), 'renderEntrySidebar']);
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event): void {
            $event->permissions[] = [
                'heading' => Craft::t('workflow', 'Workflow'),
                'permissions' => [
                    'workflow-overview' => ['label' => Craft::t('workflow', 'Overview')],
                ],
            ];
        });
    }

    private function _registerResaveCommand(): void
    {
        if (!Craft::$app instanceof ConsoleApplication) {
            return;
        }

        Event::on(ResaveController::class, ConsoleController::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
            $event->actions['workflow-submissions'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    return $controller->resaveElements(Submission::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Workflow submissions.',
            ];
        });
    }

    private function _registerGraphQl(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypesEvent $event) {
            $event->types[] = SubmissionInterface::class;
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            foreach (SubmissionQuery::getQueries() as $key => $value) {
                $event->queries[$key] = $value;
            }
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS, function(RegisterGqlSchemaComponentsEvent $event) {
            $label = Craft::t('workflow', 'Workflow');

            $event->queries[$label] = [
                'workflowSubmissions:read' => ['label' => Craft::t('workflow', 'View submissions')],
            ];
        });
    }
}
