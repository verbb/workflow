<?php
namespace verbb\workflow\helpers;

use craft\helpers\StringHelper as CraftStringHelper;

class StringHelper extends CraftStringHelper
{
    // Static Methods
    // =========================================================================

    public static function sanitizeNotes(?string $value): ?string
    {
        // Support Emojis and sanitize HTML
        $value = self::emojiToShortcodes((string)$value);
        $value = StringHelper::htmlEncode((string)$value);

        return $value;
    }

    public static function unSanitizeNotes(?string $value): ?string
    {
        // Support Emojis and sanitize HTML
        $value = self::shortcodesToEmoji((string)$value);

        return $value;
    }
}