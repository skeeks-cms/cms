<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */
namespace skeeks\cms\modules\admin\components\settings;
use skeeks\cms\base\Component;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\assets\AdminAsset;
use skeeks\yii2\ckeditor\CKEditorPresets;
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

    //Всплывающие окошки
    public $enableCustomConfirm     = Cms::BOOL_Y;
    public $enableCustomPromt       = Cms::BOOL_Y;

    //Языковые настройки
    public $languageCode            = "ru";


    //Настройки таблиц
    public $enabledPjaxPagination       = Cms::BOOL_Y;
    public $pageSize                    =   10;
    public $pageParamName               =   "page";

    //Настройки ckeditor
    public $ckeditorPreset              = CKEditorPresets::FULL;
    public $ckeditorSkin                = CKEditorPresets::SKIN_MOONO_COLOR;
    public $ckeditorHeight              = 400;


    public function init()
    {
        parent::init();

        \Yii::$app->language = $this->languageCode;
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['asset', 'languageCode', 'pageParamName', 'enabledPjaxPagination'], 'string'],
            [['pageSize'], 'integer'],
            [['enableCustomConfirm', 'enableCustomPromt', 'pageSize'], 'string'],
            [['ckeditorPreset', 'ckeditorSkin'], 'string'],
            [['ckeditorHeight'], 'integer'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'asset'                             => 'Дополнительные css и js админки',
            'enableCustomConfirm'               => 'Включить стилизованные окошки подтверждения (confirm)',
            'enableCustomPromt'                 => 'Включить стилизованные окошки вопрос с одним полем (promt)',
            'languageCode'                      => 'Язык интерфейса',

            'pageParamName'                     => 'Язык интерфейса',

            'enabledPjaxPagination'             => 'Включение ajax навигации',
            'pageParamName'                     => 'Названия парамтера страниц, при постраничной навигации',
            'pageSize'                          => 'Количество записей на одной странице',

            'ckeditorPreset'                    => 'Инструменты',
            'ckeditorSkin'                      => 'Тема оформления',
            'ckeditorHeight'                    => 'Высота',
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

        if ($this->enableCustomPromt == Cms::BOOL_Y)
        {
            $file = \Yii::$app->assetManager->getAssetUrl(AdminAsset::register($view), 'js/classes/modal/Promt.js');
            \Yii::$app->view->registerJsFile($file,
            [
                'depends' => [AdminAsset::className()]
            ]);
        }

        if ($this->enableCustomConfirm == Cms::BOOL_Y)
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

    /**
     * Настройки для Ckeditor, по умолчанию
     * @return array
     */
    public function getCkeditorOptions()
    {
        return [
            'preset' => $this->ckeditorPreset,
            'clientOptions' => [
                'height'    => $this->ckeditorHeight,
                'skin'      => $this->ckeditorSkin,
            ]
        ];
    }

}