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
        $value = StringHelper::htmlEncode((string)$value);

        return $value;
    }

    public static function normalizeNotesForOutput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Legacy notes may be raw, while current notes are already encoded.
        return StringHelper::sanitizeNotes(html_entity_decode($value, ENT_COMPAT, 'UTF-8'));
    }

    public static function unSanitizeNotes(?string $value): ?string
    {
        return $value;
    }
}
