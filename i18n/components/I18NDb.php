<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.12.2015
 */
namespace skeeks\cms\i18n\components;

use skeeks\cms\models\Message;
use skeeks\cms\models\SourceMessage;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\i18n\DbMessageSource;
use yii\i18n\MissingTranslationEvent;

class I18NDb extends I18N
{
    /** @var array */
    public $missingTranslationHandler = ['skeeks\cms\I18n\components\I18NDb', 'handleMissingTranslation'];

    /** @var string */
    public $sourceMessageTable = '{{%source_message}}';

    /** @var string */
    public $messageTable = '{{%message}}';

    public $db = 'db';

    public function getLanguages()
    {
        return array_keys(\Yii::$app->cms->languages);
    }
    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!isset($this->translations['*']))
        {
            $this->translations['*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'on missingTranslation' => $this->missingTranslationHandler
            ];
        }

        if (!isset($this->translations['app']) && !isset($this->translations['app*'])) {
            $this->translations['app'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'on missingTranslation' => $this->missingTranslationHandler
            ];
        }

        parent::init();
    }

    public static function handleMissingTranslation(MissingTranslationEvent $event)
    {
        \Yii::info("@DB: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @", self::className() . "::handleMissingTranslation");

        $driver = \Yii::$app->getDb()->getDriverName();
        $caseInsensitivePrefix = $driver === 'mysql' ? 'binary' : '';
        $sourceMessage = SourceMessage::find()
            ->where('category = :category and message = ' . $caseInsensitivePrefix . ' :message', [
                ':category' => $event->category,
                ':message' => $event->message
            ])
            ->with('messages')
            ->one();

        if (!$sourceMessage)
        {
            \Yii::info("@WRITE TO DB: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @", self::className() . "::handleMissingTranslation");

            $sourceMessage = new SourceMessage();
            $sourceMessage->setAttributes([
                'category' => $event->category,
                'message' => $event->message
            ], false);
            $sourceMessage->save(false);
        }

        $sourceMessage->initMessages();
        $sourceMessage->saveMessages();

        $messages = $sourceMessage->messages;

        if (isset($messages[\Yii::$app->sourceLanguage]))
        {
            $message = $messages[\Yii::$app->sourceLanguage];
            $message->translation = $sourceMessage->message;
            $message->save(false);
        }

        /**
         * @var $message Message
         */
        $message = ArrayHelper::getValue($sourceMessage->messages, \Yii::$app->language);
        if ($message)
        {
            $event->translatedMessage = $message->translation;
        }
    }
}