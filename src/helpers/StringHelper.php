<?php
namespace verbb\workflow\helpers;

use craft\helpers\StringHelper as CraftStringHelper;

use LitEmoji\LitEmoji;

class StringHelper extends CraftStringHelper
{
    // Static Methods
    // =========================================================================

    public static function sanitizeNotes($value): ?string
    {
        // Support Emojis and sanitize HTML
        $value = LitEmoji::unicodeToShortcode($value);
        $value = StringHelper::htmlEncode($value);

        return $value;
    }

    public static function unSanitizeNotes($value): ?string
    {
        // Support Emojis and sanitize HTML
        $value = LitEmoji::shortcodeToUnicode($value);

        return $value;
    }
}