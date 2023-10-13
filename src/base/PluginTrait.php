<?php
namespace verbb\workflow\base;

use verbb\workflow\Workflow;
use verbb\workflow\services\Content;
use verbb\workflow\services\Emails;
use verbb\workflow\services\Reviews;
use verbb\workflow\services\Service;
use verbb\workflow\services\Submissions;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Workflow $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;


    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('workflow');

        return [
            'components' => [
                'content' => Content::class,
                'emails' => Emails::class,
                'reviews' => Reviews::class,
                'service' => Service::class,
                'submissions' => Submissions::class,
            ],
        ];
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

}
