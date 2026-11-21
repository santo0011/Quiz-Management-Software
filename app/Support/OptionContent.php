<?php

namespace App\Support;

/**
 * Answer options come in two stored formats: legacy plain text (everything
 * saved before the rich option editor, rendered escaped) and sanitized
 * CKEditor HTML, which always starts with one of CKEditor's block elements.
 * Telling them apart at read time keeps every existing option displaying
 * exactly as before without rewriting stored data.
 */
class OptionContent
{
    // ~2 MB of image after base64 encoding (the editor rejects larger files),
    // with room for the surrounding markup.
    public const MAX_LENGTH = 3_000_000;

    private const HTML_PATTERN = '/^\s*<(p|figure|ul|ol|table|blockquote|h[2-4]|pre)[\s>]/i';

    // Options render inside the student's clickable answer button, where a
    // link would navigate away mid-exam.
    private const EXCLUDED_TAGS = ['a'];

    public static function isHtml(?string $value): bool
    {
        return preg_match(self::HTML_PATTERN, (string) $value) === 1;
    }

    /**
     * Prepares submitted option input for storage: rich HTML is sanitized,
     * and HTML with no visible content collapses to '' so `required` rejects
     * it. Plain text (the no-JavaScript fallback) is stored as-is.
     */
    public static function normalize(mixed $value): string
    {
        $value = trim((string) $value);

        if (! self::isHtml($value)) {
            return $value;
        }

        $clean = HtmlSanitizer::sanitize($value, self::EXCLUDED_TAGS);

        if (self::isBlank($clean)) {
            return '';
        }

        return self::isHtml($clean) ? $clean : '<p>'.$clean.'</p>';
    }

    public static function isBlank(?string $value): bool
    {
        $value = (string) $value;

        if (! self::isHtml($value)) {
            return trim($value) === '';
        }

        if (stripos($value, '<img') !== false) {
            return false;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $text)) === '';
    }

    public static function toHtml(?string $value): string
    {
        return self::isHtml($value)
            ? HtmlSanitizer::sanitize($value, self::EXCLUDED_TAGS)
            : e((string) $value);
    }
}
