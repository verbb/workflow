<?php
namespace verbb\workflow\variables;

use verbb\workflow\Workflow;
use verbb\workflow\elements\Submission;
use verbb\workflow\elements\db\SubmissionQuery;

use Craft;
use craft\helpers\UrlHelper;

class WorkflowVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): Workflow
    {
        return Workflow::$plugin;
    }

    public function getPluginName(): string
    {
        return Workflow::$plugin->getSettings()->pluginName;
    }

    public function submissions(array $criteria = []): SubmissionQuery
    {
        $query = Submission::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function getSettingsNavItems(): array
    {
        return [
            'general' => ['title' => Craft::t('workflow', 'General Settings'), 'url' => UrlHelper::cpUrl('workflow/settings/general')],
            'notifications' => ['title' => Craft::t('workflow', 'Notifications'), 'url' => UrlHelper::cpUrl('workflow/settings/notifications')],
            'permissions' => ['title' => Craft::t('workflow', 'Permissions'), 'url' => UrlHelper::cpUrl('workflow/settings/permissions')],
        ];
    }
}
