<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */
namespace skeeks\cms\modules\admin\components\settings;
use skeeks\cms\base\Component;
use yii\web\View;

/**
 * Class AdminSettings
 * @package skeeks\cms\modules\admin\components\settings
 */
class AdminSettings extends Component
{
    public $asset;

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function getDescriptorConfig()
    {
        return
        [
            'name'          => 'Настройки админ панели',
        ];
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['asset'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'asset'                      => 'Дополнительные css и js админки',
        ]);
    }

    /**
     * Регистрация дополнительных asset
     * @param View $view
     * @return $this
     */
    public function registerAsset(View $view)
    {
        if (!$this->asset)
        {
            return $this;
        }

        if (class_exists($this->asset))
        {
            $className = $this->asset;
            $className::register($view);
        }

        return $this;
    }


}