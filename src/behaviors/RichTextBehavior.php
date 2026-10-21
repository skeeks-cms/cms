<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\behaviors;

use skeeks\cms\helpers\RichTextHtml;
use skeeks\cms\services\TemporaryUploads;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;
use yii\helpers\Html;

/**
 * Rich-text attributes edited with RichTextInputWidget.
 *
 * When such an attribute changes, its HTML is cleaned (RichTextHtml::clean())
 * and the images pasted into the editor move from the temporary upload area
 * into the storage, their temporary references replaced with storage URLs.
 * Nothing reaches the storage before the record is saved. Unchanged values
 * are never touched, so old texts stay as they are until edited.
 *
 * <code>
 * RichTextBehavior::class => [
 *     'class' => RichTextBehavior::class,
 *     'attributes' => [
 *         'comment' => 'basic',                                          // preset of the widget toolbar
 *         'description' => ['preset' => 'full', 'images' => true],       // preset with overrides
 *         'description_full' => false,                                   // trusted HTML: only images are stored
 *     ],
 * ],
 * </code>
 *
 * Text without tags (plain text from an API) is not cleaned.
 */
class RichTextBehavior extends Behavior
{
    /** @var array attribute => preset name or RichTextHtml::clean() options, or false for trusted HTML */
    public $attributes = [];

    /** Whether the model has this behavior for the attribute (pasted pictures are stored on save). */
    public static function covers($model, string $attribute): bool
    {
        if (!$model instanceof \yii\base\Component) {
            return false;
        }
        foreach ($model->getBehaviors() as $behavior) {
            if ($behavior instanceof self && array_key_exists($attribute, $behavior->attributes)) {
                return true;
            }
        }

        return false;
    }

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
        ];
    }

    public function beforeValidate(): void
    {
        foreach ($this->changedAttributes() as $attribute => $options) {
            $this->clean($attribute, $options);
            foreach (RichTextHtml::uploadTokens((string)$this->owner->$attribute) as $token) {
                if (!TemporaryUploads::path($token)) {
                    $this->owner->addError($attribute, 'Картинка «'.TemporaryUploads::name($token).'» устарела: вставьте её заново.');
                }
            }
        }
    }

    public function beforeSave(): void
    {
        foreach ($this->changedAttributes() as $attribute => $options) {
            $this->clean($attribute, $options);
            $stored = [];
            $this->owner->$attribute = RichTextHtml::replaceUploads((string)$this->owner->$attribute, static function ($token, $tag) use (&$stored) {
                if (!isset($stored[$token])) {
                    if (!TemporaryUploads::path($token)) {
                        return '';
                    }
                    $site = \Yii::$app->has('skeeks') ? \Yii::$app->skeeks->site : null;
                    $stored[$token] = (string)TemporaryUploads::store($token, $site ? ['cms_site_id' => (int)$site->id] : [])->src;
                }
                $tag = preg_replace('/\s(src|data-sx-upload)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);

                return preg_replace('/^<img\b/i', '<img src="'.Html::encode($stored[$token]).'"', (string)$tag);
            });
        }
    }

    private function changedAttributes(): array
    {
        $result = [];
        foreach ($this->attributes as $attribute => $options) {
            if ($this->owner->getIsNewRecord()) {
                $result[$attribute] = $options;
            } elseif ($this->owner->isAttributeChanged($attribute, false)) {
                // Browsers post textarea line breaks as CRLF: an untouched old text keeps its exact value.
                $old = $this->owner->getOldAttribute($attribute);
                if (str_replace("\r\n", "\n", (string)$this->owner->$attribute) === str_replace("\r\n", "\n", (string)$old)) {
                    $this->owner->$attribute = $old;
                    continue;
                }
                $result[$attribute] = $options;
            }
        }

        return $result;
    }

    private function clean(string $attribute, $options): void
    {
        $value = (string)$this->owner->$attribute;
        if ($options === false || !preg_match('~<[a-z!/]~i', $value)) {
            return;
        }
        $this->owner->$attribute = RichTextHtml::clean($value, $options);
    }
}
