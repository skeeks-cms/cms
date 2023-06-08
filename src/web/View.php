<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms\web;

use skeeks\cms\base\Theme;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteTheme;
use skeeks\cms\models\CmsTheme;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;
use yii\web\Application;

/**
 * @property array $availableThemes
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class View extends \yii\web\View
{
    /**
     * @var string
     */
    public $defaultThemeId = '';

    /**
     * @var array Доступные темы
     */
    public $themes = [
        /*'one' =>
        [
            'class' => 'skeeks\cms\view\ThemeComponent',
            'sites' => [
                1, 20
            ],
        ]*/
    ];


    /**
     * @var null
     */
    protected $_availableThemes = null;
    /**
     * @return array
     */
    public function getAvailableThemes(CmsSite $site = null)
    {
        if ($this->_availableThemes === null) {
            if ($site === null) {
                $site = \Yii::$app->skeeks->site;
            } 
            

            $result = [];
            foreach ($this->themes as $id => $themeData)
            {
                //Если тема доступна не для всех сайтов
                $siteIds = (array) ArrayHelper::getValue($themeData, 'site_ids');
                if ($siteIds) {
                    //Если для текущего сайта шаблон недоступен, то пропускаем его
                    if (!in_array($site->id, $siteIds)) {
                        continue;
                    }
                }

                $result[$id] = $themeData;
            }

            $this->_availableThemes = $result;
        }


        return $this->_availableThemes;
    }

    /**
     * @param $themes
     * @return $this
     */
    public function setAvailableThemes($themes = [])
    {
        $this->_availableThemes = $themes;
        return $this;
    }

    /**
     * @return void
     */
    public function init()
    {
        if (!$this->availableThemes) {
            return parent::init();
        }

        //Поиск настроек сохраненных в базу данных
        $cmsTheme = CmsTheme::getDb()->cache(function ($db) {
            return CmsTheme::find()->cmsSite()->active()->one();
        },
            null,
            new TagDependency([
                'tags' => [
                    (new CmsTheme())->getTableCacheTagCmsSite(),
                    \Yii::$app->skeeks->site->cacheTag
                ],
            ])
        );


        $themeData = [];
        if ($cmsTheme) {
            //Бурется настройки из конфига
            /**
             * @var $cmsTheme CmsTheme
             */
            $themeData = (array)ArrayHelper::getValue($this->availableThemes, $cmsTheme->code);
            //$themeData = ArrayHelper::merge($themeData, (array) $cmsTheme->config);

            //print_r($themeData);die;
        } else {
            if ($this->defaultThemeId) {
                $themeData = (array)ArrayHelper::getValue($this->availableThemes, $this->defaultThemeId);
            }
        }

        if ($themeData) {
            $this->theme = $themeData;
        }

        //Тут создание объекта тему
        parent::init();

        //Из настроек сайта выбралась тема
        $defaultSelectTheme = $this->theme;

        //Перед рендером темы, нужно проверить та ли тема сейчас установлена?
        //Она могла переопределиться в процессе
        \Yii::$app->on(Application::EVENT_BEFORE_ACTION, function ($event) use ($cmsTheme, $defaultSelectTheme) {
            if (\Yii::$app->controller && \Yii::$app->controller->module && in_array(\Yii::$app->controller->module->id, ['debug', 'gii'])) {
                return false;
            }
            if ($this->theme instanceof $defaultSelectTheme) {
                //Если тема та, то нужно установить настройки в тему из базы данных
                if ($cmsTheme) {
                    $cmsTheme->loadConfigToTheme($this->theme);
                    $this->theme->trigger(static::EVENT_BEFORE_RENDER, $event);
                }
            }
        });
    }

    /**
     * @return string
     */
    /*public function getCurrentThemeId()
    {
        $cmsTheme = CmsTheme::find()->cmsSite()->active()->one();
        if ($cmsTheme) {
            return (string) $cmsTheme->code;
        }

        return $this->defaultThemeId;
    }*/
}