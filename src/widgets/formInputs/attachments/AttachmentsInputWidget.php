<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\formInputs\attachments;

use skeeks\cms\controllers\TemporaryUploadController;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\widgets\formInputs\richText\RichTextInputWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;

/**
 * Compact file attachments of a form, as in the letter form: a light
 * "Прикрепить" button (button(), placed by the form next to its send
 * button), files dropped anywhere on the form or pasted from the clipboard,
 * and the attached files as chips with their size and ×.
 *
 * Files go to the temporary upload area (TemporaryUploads) at once and reach
 * the storage only when the form is saved: the field posts their tokens.
 * HasStorageFileMulti stores tokens of the current session by itself, so a
 * model with that behavior (CmsLog, CmsTask "fileIds") needs nothing else;
 * other consumers call TemporaryUploads::store() for each posted token.
 *
 * The drop area is the closest form (or [data-sx-attach-zone]). Files given
 * to a RichTextInputWidget of that area with attachLabel (its "Прикрепить
 * файлом" zone, non-image files, screenshots in a text without images) are
 * attached here.
 *
 * JS: sx.Attachments.add(root, files), isBusy(context); the event
 * "sx-attach:change" on the root after a file is attached or removed.
 */
class AttachmentsInputWidget extends InputWidget
{
    /** @var array|string upload endpoint (POST "file"; JSON success, token, name, size, sizeText, message) */
    public $uploadUrl = ['/cms/temporary-upload/attach'];

    /** @var int the largest file, bytes (checked in the browser; the endpoint checks its own limit) */
    public $maxSize = TemporaryUploadController::MAX_SIZE;

    /** @var string|null message for a too large file; {name} and {size} (MB) are replaced */
    public $maxSizeMessage = null;

    /** @var string|null input name of files already in the storage (ids); null — the field name */
    public $storedName = null;

    /**
     * @var array[]|null files already in the storage: id, name, size. Null — the
     * storage ids of the field value.
     */
    public $files = null;

    /** @var array[] files of this session waiting in the temporary area: token, name, size */
    public $uploads = [];

    /** @var bool attach files dropped on the form or pasted into it */
    public $drop = true;

    /** @var array extra attributes of the chip list (an id, a data hook of the host form) */
    public $listOptions = [];

    /**
     * @var string HTML put into the chip list before the files: chips of the
     * host form's own kind (the letter's "Прикрепить из…"). A chip with the
     * class sx-attach__file looks like a file and keeps the list visible.
     */
    public $listContent = '';

    /**
     * The "Прикрепить" button. It opens the file choice of the field with the
     * given id, or of the first field in its form.
     */
    public static function button(array $options = [], string $label = 'Прикрепить'): string
    {
        $for = $options['for'] ?? null;
        unset($options['for']);
        Html::addCssClass($options, 'sx-attach-button');
        $options = array_merge([
            'type' => 'button',
            'title' => 'Прикрепить файлы (можно перетащить на форму или вставить из буфера)',
            'data-sx-attach-open' => $for ?: true,
        ], $options);

        return Html::tag('button', RichTextInputWidget::icon('paperclip', 18).Html::tag('span', Html::encode($label)), $options);
    }

    public function run()
    {
        AttachmentsAsset::register($this->view);

        $name = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : (string)$this->name;
        $storedName = $this->storedName ?: $name;

        $items = (string)$this->listContent;
        foreach ($this->storedFiles() as $file) {
            $items .= $this->chip($file['name'], (int)$file['size'], $storedName, (string)(int)$file['id']);
        }
        foreach ($this->uploads as $upload) {
            $items .= $this->chip($upload['name'], (int)$upload['size'], $name, (string)$upload['token']);
        }

        $list = $this->listOptions;
        Html::addCssClass($list, 'sx-attach__list');
        $list['data-sx-attach-list'] = true;

        $options = $this->options;
        Html::addCssClass($options, 'sx-attach');
        $options = array_merge($options, [
            'data-sx-attach' => true,
            'data-upload-url' => Url::to($this->uploadUrl),
            'data-name' => $name,
            'data-max-size' => (int)$this->maxSize,
            'data-max-size-message' => $this->maxSizeMessage,
            'data-drop' => $this->drop ? 1 : 0,
        ]);

        return Html::tag('div',
            // An empty value, so removing every file is posted too.
            Html::hiddenInput($name, '')
            .($storedName !== $name ? Html::hiddenInput($storedName, '') : '')
            .Html::tag('div', $items, $list)
            .Html::input('file', null, null, ['multiple' => true, 'hidden' => true, 'data-sx-attach-input' => true])
            .Html::tag('template', $this->chip('', 0, $name, ''), ['data-sx-attach-template' => true]),
            $options);
    }

    /** One attached file; the hidden input keeps its id or token. */
    private function chip(string $fileName, int $size, string $inputName, string $value): string
    {
        return Html::tag('span',
            RichTextInputWidget::icon('paperclip', 14)
            .Html::tag('span', Html::encode($fileName), ['class' => 'sx-attach__name', 'title' => $fileName])
            .Html::tag('span', $value !== '' ? Html::encode(\Yii::$app->formatter->asShortSize($size)) : '', ['class' => 'sx-attach__size'])
            .Html::button('×', ['class' => 'sx-attach__remove', 'data-sx-attach-remove' => true, 'aria-label' => 'Убрать файл'])
            .Html::hiddenInput($inputName.'[]', $value),
            ['class' => 'sx-attach__file', 'data-sx-attach-file' => true]);
    }

    /** @return array[] id, name, size */
    private function storedFiles(): array
    {
        if ($this->files !== null) {
            return $this->files;
        }
        $value = $this->hasModel() ? $this->model->{$this->attribute} : $this->value;
        $ids = array_filter(array_map('intval', array_filter((array)$value, static fn($v) => is_int($v) || ctype_digit((string)$v))));
        if (!$ids) {
            return [];
        }
        $files = [];
        foreach (CmsStorageFile::find()->where(['id' => $ids])->all() as $file) {
            $files[(int)$file->id] = ['id' => (int)$file->id, 'name' => $file->fileName, 'size' => (int)$file->size];
        }

        return array_values(array_filter(array_map(static fn($id) => $files[$id] ?? null, $ids)));
    }
}
