<?php
namespace verbb\workflow\widgets;

use verbb\workflow\elements\Submission;

use Craft;
use craft\base\Widget;

class Submissions extends Widget
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('workflow', 'Workflow Submissions');
    }

    public static function iconPath(): string
    {
        return Craft::getAlias('@verbb/workflow/icon-mask.svg');
    }



    // Properties
    // =========================================================================

    public int $limit = 10;
    public string $status = 'pending';
    

    // Public Methods
    // =========================================================================
    
    public function getBodyHtml(): ?string
    {
        $submissions = Submission::find()
            ->status($this->status)
            ->limit($this->limit)
            ->all();

        return Craft::$app->getView()->renderTemplate('workflow/_widget/body', [
            'submissions' => $submissions,
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('workflow/_widget/settings', [
            'widget' => $this,
        ]);
    }

}