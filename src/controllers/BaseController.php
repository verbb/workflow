<?php
namespace verbb\workflow\controllers;

use Craft;
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

        return $this->renderTemplate('workflow/settings', [
            'settings' => $settings,
        ]);
    }
}
