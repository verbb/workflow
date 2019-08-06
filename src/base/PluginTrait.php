<?php
namespace verbb\workflow\base;

use verbb\workflow\Workflow;
use verbb\workflow\services\Service;
use verbb\workflow\services\Submissions;

use Craft;
use craft\log\FileTarget;

use yii\log\Logger;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

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
            'service' => Service::class,
            'submissions' => Submissions::class,
        ]);
    }

    private function _setLogging()
    {
        Craft::getLogger()->dispatcher->targets[] = new FileTarget([
            'logFile' => Craft::getAlias('@storage/logs/workflow.log'),
            'categories' => ['workflow'],
        ]);
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'workflow');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'workflow');
    }

}