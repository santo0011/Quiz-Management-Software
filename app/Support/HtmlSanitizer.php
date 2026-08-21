<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Strips a rich-text HTML fragment (produced by the Summary CKEditor field)
 * down to a safe allowlist of tags/attributes before it is stored or rendered
 * as raw HTML. Without this, a stored payload like <img onerror=...> or
 * <script> submitted via a raw POST (bypassing CKEditor's own UI filtering)
 * would execute for every admin/branch/student who later views the summary.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'a', 'ul', 'ol', 'li', 'blockquote',
        'h2', 'h3', 'h4',
        'figure', 'figcaption', 'img',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'span', 'code', 'pre',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    private const ALLOWED_LINK_SCHEMES = ['http', 'https', 'mailto'];

    private const ALLOWED_IMAGE_SCHEMES = ['http', 'https'];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root__">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $root = $xpath->query('//*[@id="__root__"]')->item(0);

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::cleanNode($document, $root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanNode(DOMDocument $document, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE || ! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Drop the wrapper but keep its (recursively cleaned) children
                // so removing e.g. a stray <div> doesn't delete real content.
                self::cleanNode($document, $child);

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            self::stripDisallowedAttributes($child, $tag);

            if ($tag === 'a') {
                self::sanitizeUrlAttribute($child, 'href', self::ALLOWED_LINK_SCHEMES);
            }

            if ($tag === 'img') {
                self::sanitizeImageSrc($child);
            }

            self::cleanNode($document, $child);
        }
    }

    private static function stripDisallowedAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    private static function sanitizeUrlAttribute(DOMElement $element, string $attribute, array $allowedSchemes): void
    {
        $value = $element->getAttribute($attribute);

        if ($value === '' || ! self::hasAllowedScheme($value, $allowedSchemes)) {
            $element->removeAttribute($attribute);

            return;
        }

        if ($attribute === 'href') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function sanitizeImageSrc(DOMElement $element): void
    {
        $src = $element->getAttribute('src');

        if ($src === '') {
            $element->parentNode?->removeChild($element);

            return;
        }

        // Base64 image uploads (our upload adapter) use data:image/*;base64,...
        // This is a full-string match, so nothing can be appended after it.
        if (preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,[a-zA-Z0-9+\/=]+$/', $src) === 1) {
            return;
        }

        if (self::hasAllowedScheme($src, self::ALLOWED_IMAGE_SCHEMES)) {
            return;
        }

        $element->parentNode?->removeChild($element);
    }

    /**
     * True if $value has no URI scheme (a relative reference, safe) or has
     * one that's in $allowedSchemes. Strips control characters first —
     * "java\tscript:alert(1)"-style obfuscation can make parse_url() fail to
     * recognize a scheme at all, which a naive "no scheme means safe" check
     * would then wrongly let through.
     */
    private static function hasAllowedScheme(string $value, array $allowedSchemes): bool
    {
        $normalized = ltrim(preg_replace('/[\x00-\x1F\x7F]+/', '', $value) ?? '');

        if (preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/', $normalized, $matches) !== 1) {
            return true;
        }

        return in_array(strtolower($matches[1]), $allowedSchemes, true);
    }
}
