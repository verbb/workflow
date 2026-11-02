<?php
namespace verbb\workflow\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m261102_000000_sandbox_system_messages extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Craft's Twig sandbox can't print a site or user directly, and saved messages don't pick up new defaults.
        // Only whole output tags are rewritten, so other custom wording is left as it was.
        $replacements = [
            '/\{\{\s*submission\.ownerSite\s*\}\}/' => '{{ submission.ownerSite.name }}',
            '/\{\{\s*submission\.getOwnerSite\(\)\s*\}\}/' => '{{ submission.ownerSite.name }}',
            '/\{\{\s*review\.user\s*\}\}/' => '{{ review.user.name }}',
            '/\{\{\s*submission\.editor\s*\}\}/' => '{{ submission.editor.name }}',
        ];

        $messages = (new Query())
            ->select(['id', 'subject', 'body'])
            ->from(Table::SYSTEMMESSAGES)
            ->where(['key' => ['workflow_publisher_notification', 'workflow_editor_review_notification', 'workflow_editor_notification', 'workflow_published_author_notification']])
            ->all($this->db);

        foreach ($messages as $message) {
            $subject = preg_replace(array_keys($replacements), array_values($replacements), $message['subject']);
            $body = preg_replace(array_keys($replacements), array_values($replacements), $message['body']);

            if ($subject !== $message['subject'] || $body !== $message['body']) {
                $this->update(Table::SYSTEMMESSAGES, ['subject' => $subject, 'body' => $body], ['id' => $message['id']]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261102_000000_sandbox_system_messages cannot be reverted.\n";

        return false;
    }
}
