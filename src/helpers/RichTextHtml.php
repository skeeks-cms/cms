<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\helpers;

use skeeks\cms\services\TemporaryUploads;

/**
 * HTML written in the rich-text editor (RichTextInputWidget): never trusted,
 * cleaned on the server by an allowlist of tags and toolbar styles.
 *
 * Options of clean() (they follow the editor's toolbar of the field; usually
 * given as a preset name, see PRESETS):
 * - fonts: font family (only letters);
 * - sizes: text size;
 * - colors: text and background colors (default true);
 * - tables: tables pasted earlier or kept from old texts (default false: their text stays);
 * - images: false — none; "uploads" — only editor uploads (data-sx-upload);
 *   true — uploads and pictures by http(s), site-relative or data:image URL (default "uploads").
 */
class RichTextHtml
{
    private const TAGS = ['p', 'div', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sub', 'sup', 'a', 'ul', 'ol', 'li',
        'blockquote', 'span', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'code', 'hr'];

    private const TABLE_TAGS = ['table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col'];

    /** Removed together with their content. */
    private const DROP_TAGS = ['script', 'style', 'head', 'title', 'meta', 'link', 'iframe', 'frame', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'video', 'audio', 'canvas', 'template', 'noscript'];

    private const COLOR = '/^(#[0-9a-f]{3,8}|rgba?\(\s*[\d.]+%?\s*,\s*[\d.]+%?\s*,\s*[\d.]+%?\s*(,\s*[\d.]+\s*)?\)|[a-z]{3,20})$/i';

    /** Style properties of the editor toolbar and the values each may take. */
    private const STYLES = [
        'color' => self::COLOR,
        'background-color' => self::COLOR,
        'font-size' => '/^(\d{1,3}(\.\d+)?(px|pt|em|rem|%)|xx-small|x-small|small|medium|large|x-large|xx-large|xxx-large)$/i',
        'font-family' => '/^[\w\s,"\'\-]{1,100}$/u',
        'text-align' => '/^(left|right|center|justify|start|end)$/i',
        'font-weight' => '/^(bold|bolder|normal|[1-9]00)$/i',
        'font-style' => '/^(italic|normal)$/i',
        'text-decoration' => '/^(underline|line-through|none)( (underline|line-through))?$/i',
        'text-decoration-line' => '/^(underline|line-through|none)( (underline|line-through))?$/i',
    ];

    public const QUOTE_STYLE = 'margin:0 0 0 .8ex;border-left:2px solid #ccc;padding-left:1ex';

    /**
     * Feature sets shared by the widget toolbar (RichTextInputWidget::$preset)
     * and the server cleaning, so a field never keeps what its toolbar cannot make:
     * - basic: text formatting, colors, links, quotes, lists (comments, short notes);
     * - standard: basic plus headings, text size and images;
     * - full: standard plus tables kept from pasted or old texts (tasks, content).
     * The font family is an opt-in ("fonts" => true): owner decision of
     * 2026-10-21, only letters choose a font.
     */
    public const PRESETS = [
        'basic' => ['fonts' => false, 'sizes' => false, 'colors' => true, 'tables' => false, 'images' => false],
        'standard' => ['fonts' => false, 'sizes' => true, 'colors' => true, 'tables' => false, 'images' => 'uploads'],
        'full' => ['fonts' => false, 'sizes' => true, 'colors' => true, 'tables' => true, 'images' => 'uploads'],
    ];

    /**
     * @param array|string $options clean options or a preset name (PRESETS);
     *   a preset name may be given in the options as "preset" and overridden by them
     */
    public static function options($options): array
    {
        if (is_string($options)) {
            $options = ['preset' => $options];
        }
        $preset = self::PRESETS[$options['preset'] ?? 'standard'] ?? self::PRESETS['standard'];
        unset($options['preset']);

        return array_merge($preset, $options);
    }

    /** @param array|string $options see options() */
    public static function clean(string $html, $options = []): string
    {
        $options = self::options($options);
        if (trim($html) === '') {
            return '';
        }
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        // The charset meta makes libxml read and write UTF-8 instead of numeric entities.
        $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>'.$html.'</body>', LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }
        $tags = self::TAGS;
        if ($options['tables']) {
            $tags = array_merge($tags, self::TABLE_TAGS);
        }
        if ($options['images']) {
            $tags[] = 'img';
        }
        $styles = self::STYLES;
        if (!$options['fonts']) {
            unset($styles['font-family']);
        }
        if (!$options['sizes']) {
            unset($styles['font-size']);
        }
        if (!$options['colors']) {
            unset($styles['color'], $styles['background-color']);
        }
        self::cleanNode($body, $tags, $styles, $options['images']);

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }

    private static function cleanNode(\DOMNode $node, array $tags, array $styles, $images): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }
            if (!$child instanceof \DOMElement) {
                $node->removeChild($child);
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, self::DROP_TAGS, true)) {
                $node->removeChild($child);
                continue;
            }
            self::cleanNode($child, $tags, $styles, $images);
            if (!in_array($tag, $tags, true)) {
                // Unknown wrapper: keep its content in place.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $href = $tag === 'a' ? trim((string)$child->getAttribute('href')) : '';
            $upload = $tag === 'img' ? trim((string)$child->getAttribute('data-sx-upload')) : '';
            $src = $tag === 'img' ? trim((string)$child->getAttribute('src')) : '';
            $span = [];
            foreach (['colspan', 'rowspan'] as $attribute) {
                if (in_array($tag, ['td', 'th'], true) && preg_match('/^[1-9]\d?$/', trim((string)$child->getAttribute($attribute)))) {
                    $span[$attribute] = trim((string)$child->getAttribute($attribute));
                }
            }
            $size = [];
            foreach (['width', 'height'] as $attribute) {
                if ($tag === 'img' && preg_match('/^\d{1,4}$/', trim((string)$child->getAttribute($attribute)))) {
                    $size[$attribute] = trim((string)$child->getAttribute($attribute));
                }
            }
            $style = self::cleanStyle((string)$child->getAttribute('style'), $child, $styles);
            $align = strtolower(trim((string)$child->getAttribute('align')));
            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }
            if ($tag === 'img') {
                if (TemporaryUploads::isToken($upload)) {
                    $child->setAttribute('data-sx-upload', $upload);
                } elseif ($images === true && self::isImageUrl($src)) {
                    $child->setAttribute('src', $src);
                    foreach ($size as $attribute => $value) {
                        $child->setAttribute($attribute, $value);
                    }
                } else {
                    $node->removeChild($child);
                    continue;
                }
                $child->setAttribute('alt', '');
                continue;
            }
            if ($tag === 'font') {
                // <font> from execCommand without CSS mode: keep it as a styled span.
                $replacement = $child->ownerDocument->createElement('span');
                while ($child->firstChild) {
                    $replacement->appendChild($child->firstChild);
                }
                $node->replaceChild($replacement, $child);
                $child = $replacement;
            }
            if ($align !== '' && preg_match(self::STYLES['text-align'], $align)) {
                $style = trim($style.';text-align:'.$align, ';');
            }
            if ($tag === 'a') {
                if (preg_match('~^(https?://|mailto:|tel:)~i', $href)) {
                    $child->setAttribute('href', $href);
                }
            } elseif ($tag === 'blockquote') {
                $style = self::QUOTE_STYLE;
            }
            foreach ($span as $attribute => $value) {
                $child->setAttribute($attribute, $value);
            }
            if ($style !== '') {
                $child->setAttribute('style', $style);
            }
        }
    }

    private static function isImageUrl(string $src): bool
    {
        return (bool)preg_match('~^(https?://[^\s"<>]+|/(?!/)[^\s"<>]*|data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+)$~i', $src);
    }

    /** Only the allowed style properties with values of the expected form. */
    private static function cleanStyle(string $style, \DOMElement $element, array $styles): string
    {
        $result = [];
        // <font color= face= size=>, as old editors and execCommand produce.
        if (strtolower($element->nodeName) === 'font') {
            $sizes = ['1' => 'x-small', '2' => 'small', '3' => 'medium', '4' => 'large', '5' => 'x-large', '6' => 'xx-large', '7' => 'xxx-large'];
            $style .= ';color:'.$element->getAttribute('color').';font-family:'.$element->getAttribute('face')
                .';font-size:'.($sizes[trim((string)$element->getAttribute('size'))] ?? '');
        }
        foreach (explode(';', $style) as $declaration) {
            if (strpos($declaration, ':') === false) {
                continue;
            }
            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim(preg_replace('/\s*!important$/i', '', $value));
            if ($value !== '' && isset($styles[$property]) && preg_match($styles[$property], $value)) {
                $result[$property] = $property.':'.$value;
            }
        }

        return implode(';', $result);
    }

    /** @return string[] tokens of the editor uploads (data-sx-upload) inside the HTML */
    public static function uploadTokens(string $html): array
    {
        preg_match_all('/<img[^>]*\bdata-sx-upload="([^"]+)"/i', $html, $m);
        $tokens = array_map(static fn($t) => html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $m[1]);

        return array_values(array_unique(array_filter($tokens, [TemporaryUploads::class, 'isToken'])));
    }

    /**
     * Replaces every editor upload image with the result of $callback(token,
     * img tag): an <img> tag or '' to remove it.
     */
    public static function replaceUploads(string $html, callable $callback): string
    {
        return (string)preg_replace_callback('/<img\b[^>]*\bdata-sx-upload="([^"]+)"[^>]*>/i', static function ($m) use ($callback) {
            $token = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return TemporaryUploads::isToken($token) ? (string)$callback($token, $m[0]) : '';
        }, $html);
    }

    /** True when the text has no visible content: no letters and no pictures. */
    public static function isEmpty(string $html): bool
    {
        return trim(str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) === ''
            && stripos($html, '<img') === false;
    }

    /** Plain text of HTML: line breaks, list markers and link addresses kept. */
    public static function toText(string $html): string
    {
        $html = preg_replace('~<(style|script|head|title)\b[^>]*>.*?</\1>~is', '', $html);
        $html = preg_replace('~<br\s*/?>~i', "\n", (string)$html);
        $html = preg_replace('~<li\b[^>]*>~i', "\n• ", (string)$html);
        // A list item starts its own line already; its end adds none.
        $html = preg_replace('~</(p|div|h[1-6]|ul|ol|blockquote|pre|tr)>~i', "\n", (string)$html);
        $html = preg_replace_callback('~<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>~is', static function ($m) {
            $text = trim(strip_tags($m[2]));
            $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return $text === '' || $text === $url ? $url : $text.' ('.$url.')';
        }, (string)$html);
        $text = html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", str_replace("\xc2\xa0", ' ', $text));

        return trim((string)preg_replace("/\n{3,}/", "\n\n", (string)$text));
    }

    /** Plain text as HTML: escaped, line breaks kept. */
    public static function fromText(string $text): string
    {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), false);
    }
}
