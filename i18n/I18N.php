<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.12.2015
 */
namespace skeeks\cms\i18n;

use yii\base\InvalidConfigException;
use yii\i18n\MissingTranslationEvent;

class I18N extends \yii\i18n\I18N
{
    /** @var array */
    public $missingTranslationHandler = ['skeeks\cms\I18n\I18N', 'handleMissingTranslation'];

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!isset($this->translations['*']))
        {
            $this->translations['*'] = [
                'class' => 'yii\i18n\PhpMessageSource'
            ];
        }

        parent::init();
    }

    public static function handleMissingTranslation(MissingTranslationEvent $event)
    {
        \Yii::info("@MISSING: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @", self::className());

        if ($event->category != 'skeeks/cms')
        {
            $event->translatedMessage = \Yii::t('skeeks/cms', $event->message);
        }
    }
}