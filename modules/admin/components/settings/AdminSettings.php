<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */
namespace skeeks\cms\modules\admin\components\settings;
use skeeks\cms\base\Component;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\helpers\ArrayHelper;
use yii\web\View;

/**
 * Class AdminSettings
 * @package skeeks\cms\modules\admin\components\settings
 */
class AdminSettings extends Component
{
    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Настройки админ панели',
        ]);
    }

    public $asset;

    public $enableCustomConfirm   = 1;
    public $enableCustomPromt     = 1;


    public function init()
    {
        parent::init();
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['asset'], 'string'],
            [['enableCustomConfirm', 'enableCustomPromt'], 'integer'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'asset'                             => 'Дополнительные css и js админки',
            'enableCustomConfirm'               => 'Включить стилизованные окошки подтверждения (confirm)',
            'enableCustomPromt'                 => 'Включить стилизованные окошки вопрос с одним полем (promt)',
        ]);
    }

    /**
     * Регистрация дополнительных asset
     * @param View $view
     * @return $this
     */
    public function registerAsset(View $view)
    {
        if ($this->asset)
        {
            if (class_exists($this->asset))
            {
                $className = $this->asset;
                $className::register($view);
            }
        }

        if ($this->enableCustomPromt)
        {
            $file = \Yii::$app->assetManager->getAssetUrl(AdminAsset::register($view), 'js/classes/modal/Promt.js');
            \Yii::$app->view->registerJsFile($file,
            [
                'depends' => [AdminAsset::className()]
            ]);
        }

        if ($this->enableCustomConfirm)
        {
            $file = \Yii::$app->assetManager->getAssetUrl(AdminAsset::register($view), 'js/classes/modal/Confirm.js');
            \Yii::$app->view->registerJsFile($file,
            [
                'depends' => [AdminAsset::className()]
            ]);
        }
        return $this;
    }

    /**
     * layout пустой?
     * @return bool
     */
    public function isEmptyLayout()
    {
        if (UrlHelper::constructCurrent()->getSystem(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT))
        {
            return true;
        }

        return false;
    }

}