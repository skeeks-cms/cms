<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\Serialize;
use skeeks\cms\sms\SmsHandler;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_sms_provider".
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property string      $name
 * @property int         $priority
 * @property int|null    $is_main
 * @property string      $component
 * @property string|null $component_config
 *
 * @property CmsSite     $cmsSite
 * @property SmsHandler  $handler
 */
class CmsSmsProvider extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_sms_provider';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            Serialize::class => [
                'class'  => Serialize::class,
                'fields' => ['component_config'],
            ],
        ]);
    }

    protected $_handler = null;

    /**
     * @return SmsHandler
     */
    public function getHandler()
    {
        if ($this->_handler !== null) {
            return $this->_handler;
        }

        if ($this->component) {
            try {

                $componentConfig = ArrayHelper::getValue(\Yii::$app->cms->smsHandlers, $this->component);

                $component = \Yii::createObject($componentConfig);
                $component->load($this->component_config, "");

                $this->_handler = $component;
                return $this->_handler;
            } catch (\Exception $e) {
                \Yii::error("Related property handler not found '{$this->component}'", self::class);
                return null;
            }

        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'priority', 'is_main'], 'integer'],
            [['name', 'component'], 'required'],
            [['name', 'component'], 'string', 'max' => 255],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],

            [
                'is_main',
                function() {
                    if ($this->is_main != 1) {
                        $this->is_main = null;
                    }
                },
            ],

            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            //[['cms_site_id', 'is_main'], 'unique', 'targetAttribute' => ['cms_site_id', 'is_main']],

            [['component_config'], 'safe'],
            [['component'], 'string', 'max' => 255],

            [['component_config', 'component'], 'default', 'value' => null],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_site_id'      => 'Сайт',
            'name'             => 'Название',
            'priority'         => 'Приоритет',
            'is_main'          => 'Провайдер по умолчанию?',
            'component'        => 'Провайдер',
            'component_config' => 'настройки провайдера',
        ]);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        $className = \Yii::$app->skeeks->siteClass;
        return $this->hasOne($className::className(), ['id' => 'cms_site_id']);
    }

    /**
     * @param      $phone
     * @param      $text
     * @return CmsSmsMessage
     * @throws \Exception
     */
    public function send($phone, $text)
    {
        $provider_message_id = '';

        $cmsSmsMessage = new CmsSmsMessage();

        $cmsSmsMessage->phone = $phone;
        $cmsSmsMessage->message = $text;
        $cmsSmsMessage->cms_sms_provider_id = $this->id;

        try {

            $this->handler->sendMessage($cmsSmsMessage);

            if (!$cmsSmsMessage->save()) {
                throw new Exception(print_r($cmsSmsMessage->errors, true));
            }

        } catch (\Exception $exception) {
            $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
            $cmsSmsMessage->error_message = $exception->getMessage();
            if (!$cmsSmsMessage->save()) {
                throw new Exception(print_r($cmsSmsMessage->errors, true));
            }
            //throw $exception;
        }

        return $cmsSmsMessage;
    }

}
