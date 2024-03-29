<?php
namespace verbb\workflow\base;

use verbb\workflow\Workflow;
use verbb\workflow\services\Content;
use verbb\workflow\services\Emails;
use verbb\workflow\services\Reviews;
use verbb\workflow\services\Service;
use verbb\workflow\services\Submissions;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static Workflow $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('workflow', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'workflow');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('workflow', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'workflow');
    }


    // Public Methods
    // =========================================================================

    public function getContent(): Content
    {
        return $this->get('content');
    }

    public function getEmails(): Emails
    {
        return $this->get('emails');
    }

    public function getReviews(): Reviews
    {
        return $this->get('reviews');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }

    public function getSubmissions(): Submissions
    {
        return $this->get('submissions');
    }


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $this->setComponents([
            'content' => Content::class,
            'emails' => Emails::class,
            'reviews' => Reviews::class,
            'service' => Service::class,
            'submissions' => Submissions::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('workflow');
    }

}
