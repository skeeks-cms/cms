<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\Serialize;
use skeeks\cms\telephony\TelephonyHandler;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_sms_provider".
 *
 * @property int              $id
 * @property int|null         $created_by
 * @property int|null         $updated_by
 * @property int|null         $created_at
 * @property int|null         $updated_at
 * @property string           $name
 * @property int              $priority
 * @property int|null         $is_active
 * @property string           $component
 * @property string|null      $component_config
 *
 * @property TelephonyHandler $handler
 */
class CmsTelephonyProvider extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_telephony_provider';
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

                $componentConfig = ArrayHelper::getValue(\Yii::$app->cms->telephonyHandlers, $this->component);

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


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['name', 'component'], 'required'],
            [['priority', 'is_active'], 'integer'],
            [['component_config'], 'safe'],
            [['name', 'component'], 'string', 'max' => 255],

            [['component_config', 'component'], 'default', 'value' => null],


            [
                'name',
                'unique'
            ],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name'             => 'Название',
            'priority'         => 'Приоритет',
            'is_active'          => 'Включен?',
            'component'        => 'Компонент',
            'component_config' => 'Конфигурация компонента',
        ]);
    }



}
