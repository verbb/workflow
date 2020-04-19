<?php
namespace verbb\workflow\base;

use verbb\workflow\Workflow;
use verbb\workflow\services\Service;
use verbb\workflow\services\Submissions;

use Craft;
use craft\log\FileTarget;

use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    /**
     * @var Workflow $plugin
     */
    public static $plugin;


    // Public Methods
    // =========================================================================

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->get('service');
    }

    /**
     * @return Submissions
     */
    public function getSubmissions()
    {
        return $this->get('submissions');
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'workflow');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'workflow');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'service' => Service::class,
            'submissions' => Submissions::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging()
    {
        BaseHelper::setFileLogging('workflow');
    }

}
