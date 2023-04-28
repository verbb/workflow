<?php
namespace verbb\workflow\helpers;

use craft\helpers\StringHelper as CraftStringHelper;

use LitEmoji\LitEmoji;

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

    public static function emojiToShortcodes(string $str): string
    {
        // Add delimiters around all 4-byte chars
        $dl = '__MB4_DL__';
        $dr = '__MB4_DR__';
        $str = self::replaceMb4($str, fn($char) => sprintf('%s%s%s', $dl, $char, $dr));

        // Strip out consecutive delimiters
        $str = str_replace(sprintf('%s%s', $dr, $dl), '', $str);

        // Replace all 4-byte sequences individually
        return preg_replace_callback("/$dl(.+?)$dr/", fn($m) => LitEmoji::unicodeToShortcode($m[1]), $str);
    }

    public static function shortcodesToEmoji(string $str): string
    {
        return LitEmoji::shortcodeToUnicode($str);
    }
}