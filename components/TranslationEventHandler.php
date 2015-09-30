<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 01.10.2015
 */

namespace skeeks\cms\components;
use yii\i18n\MissingTranslationEvent;

/**
 * Class TranslationEventHandler
 * @package skeeks\cms\components
 */
class TranslationEventHandler
{
    public static function handleMissingTranslation(MissingTranslationEvent $event)
    {
        \Yii::info("@MISSING: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @");
        $event->translatedMessage = \Yii::t('app', $event->message);
    }
}