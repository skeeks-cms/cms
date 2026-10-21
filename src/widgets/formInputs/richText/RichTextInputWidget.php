<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\formInputs\richText;

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\controllers\AdminRichTextController;
use skeeks\cms\helpers\RichTextHtml;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;

/**
 * Light rich-text editor for a form field: a contenteditable area with a
 * toolbar (undo, headings, bold/italic/underline/strike, size, colors, clear,
 * smileys, links, images, quote, alignment, lists; the font family only on
 * request), cleaning of text pasted from Word or a browser, screenshots
 * pasted and pictures dragged in (two drop zones: into the text or, with
 * attachLabel, to the form's attachments). The toolbar sticks to the top
 * while a long text scrolls. Colors follow the backend theme (--sx-*
 * variables, light and dark).
 *
 * The value lives in a hidden textarea and changes only when the text is
 * edited: an untouched field posts its old value byte for byte. Images go to
 * the temporary upload area and are stored by RichTextBehavior on save.
 * Never trust the posted HTML: give the model RichTextBehavior with the same
 * preset, or clean it with RichTextHtml::clean().
 *
 * <code>
 * $form->field($model, 'description')->widget(RichTextInputWidget::class, ['preset' => 'standard']);
 * $form->field($model, 'comment')->widget(RichTextInputWidget::class, ['preset' => 'basic', 'minHeight' => 100]);
 * </code>
 */
class RichTextInputWidget extends InputWidget
{
    /** Toolbar of each preset (RichTextHtml::PRESETS has the matching server cleaning). "|" is a separator. */
    public const PRESET_TOOLS = [
        'basic' => ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'strike', '|', 'color', 'background', '|', 'clear', '|',
            'emoji', 'link', 'unlink', 'quote', '|', 'align', 'ul', 'ol', 'fullscreen'],
        'standard' => ['undo', 'redo', '|', 'heading', 'bold', 'italic', 'underline', 'strike', '|', 'size', 'color', 'background', '|',
            'clear', '|', 'emoji', 'link', 'unlink', 'image', 'quote', '|', 'align', 'ul', 'ol', 'fullscreen'],
        'full' => ['undo', 'redo', '|', 'heading', 'bold', 'italic', 'underline', 'strike', '|', 'size', 'color', 'background', '|',
            'clear', '|', 'emoji', 'link', 'unlink', 'image', 'quote', '|', 'align', 'ul', 'ol', 'fullscreen'],
    ];

    /**
     * @var string|array feature set: basic, standard or full, or RichTextHtml
     * options with "preset" (e.g. ['preset' => 'standard', 'fonts' => true]).
     * Give the server cleaning the same value.
     */
    public $preset = 'standard';

    /**
     * @var string[]|null explicit toolbar instead of the preset's one; tool
     * names: undo, redo, heading, bold, italic, underline, strike, font, size,
     * color, background, clear, emoji, link, unlink, image, quote, align, ul,
     * ol, fullscreen and "|" (separator). "font" belongs only where cleaning keeps fonts.
     */
    public $tools;

    /** @var array|string|null image upload endpoint (JSON: success, token, url); null — no images */
    public $uploadUrl = ['/cms/admin-rich-text/upload'];

    /**
     * @var array|string|null endpoint that downloads a picture by URL (POST url,
     * same JSON as uploadUrl): pictures of text pasted from another site are
     * copied to us. null — pasted pictures are dropped.
     */
    public $importUrl = ['/cms/admin-rich-text/import'];

    /** @var int largest image accepted in the browser, bytes */
    public $maxImageSize = AdminRichTextController::MAX_SIZE;

    public $placeholder = '';

    /** @var int minimal height of the text area, px */
    public $minHeight = 180;

    public $autofocus = false;

    /**
     * @var string|false label of the second drop zone. When set, files dragged
     * onto it are not inserted: the root element triggers the jQuery event
     * "sx-rte:attach" with the files, and the form attaches them its own way.
     */
    public $attachLabel = false;

    /** @var bool no own frame: the editor is a part of a bigger form (a letter) */
    public $plain = false;

    /**
     * @var bool the text is trusted HTML of a site page: the visual editor
     * refuses to open texts with scripts, styles or embeds (edit them as HTML)
     */
    public $trusted = false;

    /** @var string tag of a new paragraph on Enter: "p" for articles and descriptions, "div" for letters */
    public $paragraph = 'p';

    /** @var string|null id of an existing textarea to edit instead of rendering one */
    public $inputId;

    /** @var array HTML options of the root element */
    public $wrapperOptions = [];

    /** Paragraph kinds of the "heading" menu; the label shows the current one. */
    private const BLOCKS = ['p' => 'Текст', 'h1' => 'Заголовок 1', 'h2' => 'Заголовок 2', 'h3' => 'Заголовок 3', 'h4' => 'Заголовок 4'];

    private const FONTS =['Arial', 'Georgia', 'Times New Roman', 'Verdana', 'Tahoma', 'Courier New'];
    private const SIZES = ['2' => 'Мелкий', '3' => 'Обычный', '4' => 'Крупный', '5' => 'Большой', '6' => 'Огромный'];
    private const COLORS = ['#000000', '#5f6368', '#9aa0a6', '#ffffff', '#d93025', '#e8710a', '#f9ab00', '#188038', '#1a73e8', '#9334e6', '#ee4d7d', '#795548'];
    private const EMOJI = ['😀', '😊', '😉', '😍', '🤝', '👍', '👌', '🙏', '👏', '🔥', '✅', '❗', '❓', '⭐', '🎉', '💡', '📎', '📞', '📅', '⏰', '💰', '📦', '🚀', '😔'];

    /** Toolbar glyphs absent from BackendIcon, drawn the same way (24×24, stroke). */
    private const OWN_ICONS = [
        'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>',
        'undo' => '<path d="M9 14 4 9l5-5"></path><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"></path>',
        'redo' => '<path d="m15 14 5-5-5-5"></path><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"></path>',
        'type' => '<polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" x2="15" y1="20" y2="20"></line><line x1="12" x2="12" y1="4" y2="20"></line>',
        'baseline' => '<path d="M4 20h16"></path><path d="m6 16 6-12 6 12"></path><path d="M8 12h8"></path>',
        'highlighter' => '<path d="m9 11-6 6v3h9l3-3"></path><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"></path>',
        'eraser' => '<path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"></path><path d="M22 21H7"></path><path d="m5 11 9 9"></path>',
        'smile' => '<circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" x2="9.01" y1="9" y2="9"></line><line x1="15" x2="15.01" y1="9" y2="9"></line>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
        'unlink' => '<path d="M9 17H7A5 5 0 0 1 7 7"></path><path d="M15 7h2a5 5 0 0 1 4 8"></path><line x1="8" x2="12" y1="12" y2="12"></line><line x1="2" x2="22" y1="2" y2="22"></line>',
        'quote' => '<path d="M10 11H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5c0 2.5-1.5 4-4 5"></path><path d="M19 11h-4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5c0 2.5-1.5 4-4 5"></path>',
        'align-left' => '<line x1="21" x2="3" y1="6" y2="6"></line><line x1="15" x2="3" y1="12" y2="12"></line><line x1="17" x2="3" y1="18" y2="18"></line>',
        'align-center' => '<line x1="21" x2="3" y1="6" y2="6"></line><line x1="17" x2="7" y1="12" y2="12"></line><line x1="19" x2="5" y1="18" y2="18"></line>',
        'align-right' => '<line x1="21" x2="3" y1="6" y2="6"></line><line x1="21" x2="9" y1="12" y2="12"></line><line x1="21" x2="7" y1="18" y2="18"></line>',
        'align-justify' => '<line x1="3" x2="21" y1="6" y2="6"></line><line x1="3" x2="21" y1="12" y2="12"></line><line x1="3" x2="21" y1="18" y2="18"></line>',
        'list' => '<line x1="8" x2="21" y1="6" y2="6"></line><line x1="8" x2="21" y1="12" y2="12"></line><line x1="8" x2="21" y1="18" y2="18"></line><line x1="3" x2="3.01" y1="6" y2="6"></line><line x1="3" x2="3.01" y1="12" y2="12"></line><line x1="3" x2="3.01" y1="18" y2="18"></line>',
        'list-ordered' => '<line x1="10" x2="21" y1="6" y2="6"></line><line x1="10" x2="21" y1="12" y2="12"></line><line x1="10" x2="21" y1="18" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path>',
        'paperclip' => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>',
        'expand' => '<polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" x2="14" y1="3" y2="10"></line><line x1="3" x2="10" y1="21" y2="14"></line>',
        'minimize' => '<polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" x2="21" y1="10" y2="3"></line><line x1="3" x2="10" y1="21" y2="14"></line>',
    ];

    /** A toolbar icon: BackendIcon when it has the name, otherwise the widget's own glyph. */
    public static function icon(string $name, int $size = 16): string
    {
        if (BackendIcon::supports($name)) {
            return BackendIcon::render($name, ['size' => $size]);
        }

        return Html::tag('svg', self::OWN_ICONS[$name] ?? '', [
            'class' => 'sx-icon sx-icon--'.$name, 'viewBox' => '0 0 24 24', 'width' => $size, 'height' => $size,
            'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => 2, 'stroke-linecap' => 'round',
            'stroke-linejoin' => 'round', 'focusable' => 'false', 'aria-hidden' => 'true',
        ]);
    }

    public function run()
    {
        RichTextAsset::register($this->view);

        $presetName = is_array($this->preset) ? ($this->preset['preset'] ?? 'standard') : $this->preset;
        $tools = $this->tools ?? (self::PRESET_TOOLS[$presetName] ?? self::PRESET_TOOLS['standard']);
        $images = RichTextHtml::options($this->preset)['images'] && $this->uploadUrl !== null;
        if (!$images) {
            $tools = array_values(array_diff($tools, ['image']));
        }

        if ($this->inputId) {
            $input = '';
            $inputId = $this->inputId;
        } else {
            $inputId = $this->options['id'];
            $options = array_merge($this->options, ['hidden' => true, 'class' => 'sx-rte__value', 'data-sx-rte-value' => true]);
            $input = $this->hasModel()
                ? Html::activeTextarea($this->model, $this->attribute, $options)
                : Html::textarea($this->name, $this->value, $options);
        }

        $wrapper = $this->wrapperOptions;
        Html::addCssClass($wrapper, ['sx-rte', $this->plain ? 'sx-rte--plain' : '']);
        $wrapper['data-sx-rte'] = true;
        $wrapper['data-input'] = $inputId;
        $wrapper['data-upload-url'] = $images ? Url::to($this->uploadUrl) : null;
        $wrapper['data-import-url'] = $images && $this->importUrl !== null ? Url::to($this->importUrl) : null;
        $wrapper['data-max-size'] = $this->maxImageSize;
        $wrapper['data-attach'] = $this->attachLabel ? 1 : null;
        $wrapper['data-trusted'] = $this->trusted ? 1 : null;
        $wrapper['data-autofocus'] = $this->autofocus ? 1 : null;
        $wrapper['data-paragraph'] = $this->paragraph === 'div' ? 'div' : 'p';

        $label = $this->hasModel() ? $this->model->getAttributeLabel($this->attribute) : 'Текст';

        return Html::tag('div',
            $this->renderToolbar($tools)
            .Html::tag('div', '', [
                'class' => 'sx-rte__editor',
                'contenteditable' => 'true',
                'role' => 'textbox',
                'aria-multiline' => 'true',
                'aria-label' => $label,
                'data-placeholder' => $this->placeholder,
                'data-sx-rte-editor' => true,
                'style' => 'min-height:'.(int)$this->minHeight.'px',
            ])
            .$this->renderDropZones($images)
            .($images ? Html::input('file', null, null, ['accept' => 'image/png,image/jpeg,image/gif,image/webp', 'multiple' => true, 'hidden' => true, 'data-sx-rte-image-input' => true]) : '')
            .$input,
            $wrapper);
    }

    private function renderDropZones(bool $images): string
    {
        $zones = '';
        if ($images) {
            $zones .= Html::tag('div', self::icon('image', 22).'<span>Вставить в текст</span>', ['class' => 'sx-rte__zone', 'data-sx-rte-zone' => 'insert']);
        }
        if ($this->attachLabel) {
            $zones .= Html::tag('div', self::icon('paperclip', 22).'<span>'.Html::encode($this->attachLabel).'</span>', ['class' => 'sx-rte__zone', 'data-sx-rte-zone' => 'attach']);
        }

        return $zones ? Html::tag('div', $zones, ['class' => 'sx-rte__drop', 'data-sx-rte-drop' => true, 'hidden' => true]) : '';
    }

    private function renderToolbar(array $tools): string
    {
        $tool = static fn(string $cmd, string $title, string $content, ?string $value = null) => Html::button($content, [
            'type' => 'button', 'title' => $title, 'aria-label' => $title,
            'data-sx-rte-cmd' => $cmd, 'data-value' => $value,
        ]);
        $menu = static fn(string $key, string $title, string $content, string $items, string $class = '') => Html::tag('div',
            Html::button($content.'<span class="sx-rte__caret">▾</span>', ['type' => 'button', 'title' => $title, 'aria-label' => $title, 'data-sx-rte-menu' => $key, 'aria-haspopup' => 'true'])
            .Html::tag('div', $items, ['class' => 'sx-rte__menu '.$class, 'data-sx-rte-menu-list' => $key, 'hidden' => true, 'role' => 'menu']),
            ['class' => 'sx-rte__menu-wrap']);
        $swatches = static fn(string $cmd) => implode('', array_map(static fn($c) => Html::button('', [
            'type' => 'button', 'class' => 'sx-rte__swatch', 'style' => 'background:'.$c, 'title' => $c,
            'data-sx-rte-cmd' => $cmd, 'data-value' => $c,
        ]), self::COLORS));

        $html = [];
        foreach ($tools as $name) {
            switch ($name) {
                case '|':
                    $html[] = '<span class="sx-rte__sep"></span>';
                    break;
                case 'undo':
                    $html[] = $tool('undo', 'Отменить (Ctrl+Z)', self::icon('undo'));
                    break;
                case 'redo':
                    $html[] = $tool('redo', 'Повторить (Ctrl+Y)', self::icon('redo'));
                    break;
                case 'heading':
                    $html[] = $menu('heading', 'Заголовок', '<span class="sx-rte__block-label" data-sx-rte-block>Текст</span>', implode('', array_map(
                        static fn($tag, $title) => Html::button(Html::encode($title), ['type' => 'button', 'class' => 'sx-rte__menu-item sx-rte__block-'.$tag, 'data-sx-rte-cmd' => 'formatBlock', 'data-value' => $tag]),
                        array_keys(self::BLOCKS), self::BLOCKS)));
                    break;
                case 'bold':
                    $html[] = $tool('bold', 'Жирный (Ctrl+B)', '<b>Ж</b>');
                    break;
                case 'italic':
                    $html[] = $tool('italic', 'Курсив (Ctrl+I)', '<i>К</i>');
                    break;
                case 'underline':
                    $html[] = $tool('underline', 'Подчёркнутый (Ctrl+U)', '<u>Ч</u>');
                    break;
                case 'strike':
                    $html[] = $tool('strikeThrough', 'Зачёркнутый', '<s>З</s>');
                    break;
                case 'font':
                    $html[] = $menu('font', 'Шрифт', '<span class="sx-rte__font-label" data-sx-rte-font>Шрифт</span>', implode('', array_map(
                        static fn($f) => Html::button(Html::encode($f), ['type' => 'button', 'class' => 'sx-rte__menu-item', 'style' => 'font-family:'.$f, 'data-sx-rte-cmd' => 'fontName', 'data-value' => $f]),
                        self::FONTS)));
                    break;
                case 'size':
                    $html[] = $menu('size', 'Размер текста', self::icon('type'), implode('', array_map(
                        static fn($v, $t) => Html::button(Html::encode($t), ['type' => 'button', 'class' => 'sx-rte__menu-item sx-rte__size-'.$v, 'data-sx-rte-cmd' => 'fontSize', 'data-value' => $v]),
                        array_keys(self::SIZES), self::SIZES)));
                    break;
                case 'color':
                    $html[] = $menu('color', 'Цвет текста', self::icon('baseline'), $swatches('foreColor'), 'sx-rte__menu--swatches');
                    break;
                case 'background':
                    $html[] = $menu('background', 'Цвет фона', self::icon('highlighter'),
                        $swatches('hiliteColor').Html::button('Без фона', ['type' => 'button', 'class' => 'sx-rte__menu-item sx-rte__menu-wide', 'data-sx-rte-cmd' => 'hiliteColor', 'data-value' => 'transparent']),
                        'sx-rte__menu--swatches');
                    break;
                case 'clear':
                    $html[] = $tool('removeFormat', 'Очистить оформление', self::icon('eraser'));
                    break;
                case 'emoji':
                    $html[] = $menu('emoji', 'Смайлики', self::icon('smile'), implode('', array_map(
                        static fn($e) => Html::button($e, ['type' => 'button', 'class' => 'sx-rte__emoji', 'data-sx-rte-cmd' => 'insertText', 'data-value' => $e]),
                        self::EMOJI)), 'sx-rte__menu--emoji');
                    break;
                case 'link':
                    $html[] = $tool('createLink', 'Ссылка', self::icon('link'));
                    break;
                case 'unlink':
                    $html[] = $tool('unlink', 'Убрать ссылку', self::icon('unlink'));
                    break;
                case 'image':
                    $html[] = Html::button(self::icon('image'), ['type' => 'button', 'title' => 'Картинка в тексте', 'aria-label' => 'Картинка в тексте', 'data-sx-rte-image' => true]);
                    break;
                case 'quote':
                    $html[] = $tool('formatBlock', 'Цитата', self::icon('quote'), 'blockquote');
                    break;
                case 'align':
                    $html[] = $menu('align', 'Выравнивание', self::icon('align-left'), implode('', [
                        $tool('justifyLeft', 'По левому краю', self::icon('align-left')),
                        $tool('justifyCenter', 'По центру', self::icon('align-center')),
                        $tool('justifyRight', 'По правому краю', self::icon('align-right')),
                        $tool('justifyFull', 'По ширине', self::icon('align-justify')),
                    ]), 'sx-rte__menu--row');
                    break;
                case 'ul':
                    $html[] = $tool('insertUnorderedList', 'Маркированный список', self::icon('list'));
                    break;
                case 'ol':
                    $html[] = $tool('insertOrderedList', 'Нумерованный список', self::icon('list-ordered'));
                    break;
                case 'fullscreen':
                    $html[] = Html::button(self::icon('expand').self::icon('minimize'), [
                        'type' => 'button', 'class' => 'sx-rte__fullscreen', 'title' => 'На весь экран (Esc — свернуть)',
                        'aria-label' => 'На весь экран', 'aria-pressed' => 'false', 'data-sx-rte-fullscreen' => true,
                    ]);
                    break;
            }
        }

        return Html::tag('div', implode('', $html), ['class' => 'sx-rte__tools', 'role' => 'toolbar', 'aria-label' => 'Оформление текста']);
    }
}
