<?php
namespace verbb\workflow\base;

use verbb\workflow\Workflow;
use verbb\workflow\services\Drafts;
use verbb\workflow\services\Service;
use verbb\workflow\services\Submissions;

use Craft;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getDrafts()
    {
        return $this->get('drafts');
    }

    public function getService()
    {
        return $this->get('service');
    }

    public function getSubmissions()
    {
        return $this->get('submissions');
    }

    private function _setPluginComponents()
    {
        $this->setComponents([
            'drafts' => Drafts::class,
            'service' => Service::class,
            'submissions' => Submissions::class,
        ]);
    }

}