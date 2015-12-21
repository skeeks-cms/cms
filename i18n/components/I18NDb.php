<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.12.2015
 */
namespace skeeks\cms\i18n\components;

use skeeks\cms\models\SourceMessage;
use yii\base\InvalidConfigException;
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
        return ['ru', 'en'];
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
        \Yii::info("@MISSING: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @");

        $driver = \Yii::$app->getDb()->getDriverName();
        $caseInsensitivePrefix = $driver === 'mysql' ? 'binary' : '';
        $sourceMessage = SourceMessage::find()
            ->where('category = :category and message = ' . $caseInsensitivePrefix . ' :message', [
                ':category' => $event->category,
                ':message' => $event->message
            ])
            ->with('messages')
            ->one();

        if (!$sourceMessage) {
            $sourceMessage = new SourceMessage();
            $sourceMessage->setAttributes([
                'category' => $event->category,
                'message' => $event->message
            ], false);
            $sourceMessage->save(false);
        }
        $sourceMessage->initMessages();
        $sourceMessage->saveMessages();

        if (!$event->category == 'app')
        {
            $event->translatedMessage = $sourceMessage->getMessages();
        }
    }
}