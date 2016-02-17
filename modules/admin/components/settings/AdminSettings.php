<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */
namespace skeeks\cms\modules\admin\components\settings;
use skeeks\cms\base\Component;
use skeeks\cms\base\Widget;
use skeeks\cms\cmsWidgets\admin\base\AdminBaseWidget;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsLang;
use skeeks\cms\modules\admin\assets\AdminAsset;
use skeeks\yii2\ckeditor\CKEditorPresets;
use yii\helpers\ArrayHelper;
use yii\web\View;

/**
 * @property CmsLang $cmsLanguage
 * @property [] $dasboardWidgets
 * @property [] $dasboardWidgetsLabels
 *
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
            'name'          => \Yii::t('app','Setting the admin panel'),
        ]);
    }

    public $asset;

    public $userDashboardWidgets = [];

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
    public $ckeditorPreset              = CKEditorPresets::EXTRA;
    public $ckeditorSkin                = CKEditorPresets::SKIN_MOONO_COLOR;
    public $ckeditorHeight              = 400;
    public $ckeditorCodeSnippetGeshi    = Cms::BOOL_N;
    public $ckeditorCodeSnippetTheme    = 'monokai_sublime';


    public $blockedTime                 = 900; //15 минут


    /**
     * @return array
     */
    public function getDasboardWidgets()
    {
        $baseWidgets = [
            AdminBaseWidget::className()
        ];

        $widgets = ArrayHelper::merge($baseWidgets, $this->userDashboardWidgets);

        $result = [];
        foreach ($widgets as $key => $classWidget)
        {
            if (class_exists($classWidget) && is_subclass_of($classWidget, Widget::className()))
            {
                $result[$classWidget] = $classWidget;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDasboardWidgetsLabels()
    {
        $result = [];
        if ($this->dasboardWidgets)
        {
            foreach ($this->dasboardWidgets as $widgetClassName)
            {
                $result[$widgetClassName] = (new $widgetClassName)->descriptor->name;
            }
        }

        return $result;
    }


    public function init()
    {
        parent::init();

        \Yii::$app->language = $this->languageCode;
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['languageCode', 'pageParamName', 'enabledPjaxPagination'], 'string'],
            [['pageSize'], 'integer'],
            [['ckeditorCodeSnippetGeshi'], 'string'],
            [['ckeditorCodeSnippetTheme'], 'string'],
            [['enableCustomConfirm', 'enableCustomPromt', 'pageSize'], 'string'],
            [['ckeditorPreset', 'ckeditorSkin'], 'string'],
            [['ckeditorHeight'], 'integer'],
            [['blockedTime'], 'integer', 'min' => 300],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            //'asset'                             => \Yii::t('app','Additional css and js admin area'),
            'enableCustomConfirm'               => \Yii::t('app','Include stylized window confirmation (confirm)'),
            'enableCustomPromt'                 => \Yii::t('app','Include stylized window question with one field (promt)'),
            'languageCode'                      => \Yii::t('app','Interface language'),

            'pageParamName'                     => \Yii::t('app','Interface language'),

            'enabledPjaxPagination'             => \Yii::t('app','Turning ajax navigation'),
            'pageParamName'                     => \Yii::t('app','Parameter name pages, pagination'),
            'pageSize'                          => \Yii::t('app','Number of records on one page'),

            'ckeditorPreset'                    => \Yii::t('app','Instruments'),
            'ckeditorSkin'                      => \Yii::t('app','Theme of formalization'),
            'ckeditorHeight'                    => \Yii::t('app','Height'),
            'ckeditorCodeSnippetGeshi'          => \Yii::t('app','Use code highlighting') . ' (Code Snippets Using GeSHi)',
            'ckeditorCodeSnippetTheme'          => \Yii::t('app','Theme of {theme} code',['theme' => 'hightlight']),

            'blockedTime'                       => \Yii::t('app','Time through which block user'),
        ]);
    }

    /**
     * @param View|null $view
     */
    public function initJs(View $view = null)
    {
        $options =
        [
            'BlockerImageLoader'        => AdminAsset::getAssetUrl('images/loaders/circulare-blue-24_24.GIF'),
            'disableCetainLink'         => false,
            'globalAjaxLoader'          => true,
            'menu'                      => [],
        ];

        $options = \yii\helpers\Json::encode($options);

        \Yii::$app->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            /**
            * Запускаем глобальный класс админки
            * @type {Admin}
            */
            sx.App = new sx.classes.Admin($options);

        })(sx, sx.$, sx._);
JS
        );
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
            $file = AdminAsset::getAssetUrl('js/classes/modal/Promt.js');
            //$file = \Yii::$app->assetManager->getAssetUrl(AdminAsset::register($view), 'js/classes/modal/Promt.js');
            \Yii::$app->view->registerJsFile($file,
            [
                'depends' => [AdminAsset::className()]
            ]);
        }

        if ($this->enableCustomConfirm == Cms::BOOL_Y)
        {
            $file = AdminAsset::getAssetUrl('js/classes/modal/Confirm.js');
            //$file = \Yii::$app->assetManager->getAssetUrl(AdminAsset::register($view), 'js/classes/modal/Confirm.js');
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
        $clientOptions = [
            'height'                => $this->ckeditorHeight,
            'skin'                  => $this->ckeditorSkin,
            'codeSnippet_theme'     => $this->ckeditorCodeSnippetTheme,
        ];

        if ($this->ckeditorCodeSnippetGeshi == Cms::BOOL_Y)
        {
            $clientOptions['codeSnippetGeshi_url'] = '../lib/colorize.php';

            $preset = CKEditorPresets::getPresets($this->ckeditorPreset);
            $extraplugins = ArrayHelper::getValue($preset, 'extraPlugins', "");

            if ($extraplugins)
            {
                $extraplugins = explode(",", $extraplugins);
            }

            $extraplugins = array_merge($extraplugins, ['codesnippetgeshi']);
            $extraplugins = array_unique($extraplugins);

            $clientOptions['extraPlugins'] = implode(',', $extraplugins);
        }

        return [
            'preset' => $this->ckeditorPreset,
            'clientOptions' => $clientOptions
        ];
    }

    /**
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getCmsLanguage()
    {
        return CmsLang::find()->where(['code' => $this->languageCode])->one();
    }

}