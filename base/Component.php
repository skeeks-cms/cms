<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\models\Settings;
use yii\base\Component as YiiComponent;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class Component
 * @package skeeks\cms\base
 */
class Component extends Model
{
    public function init()
    {
        parent::init();

        \Yii::trace('Cms component init: ' . $this->className());
        $this->loadDefaultSettings();
    }

    /**
     * Файл с формой настроек, по умолчанию
     *
     * @return string
     */
    public function configFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '_form.php';
    }

    /**
     * @return bool
     */
    public function hasConfigFormFile()
    {
        return file_exists($this->configFormFile());
    }

    /**
     * TODO: переписать или дописать, когда будет время
     *
     * @param array $protectedParams Параметры которые disabled
     * @return string
     */
    public function renderConfigForm($protectedParams = [])
    {
        $protectedParamsResult = [];
        if ($protectedParams)
        {
            foreach ($protectedParams as $protectedParam)
            {
                $protectedParamsResult[$protectedParam] = Html::getInputId($this, $protectedParam);
            }
        }

        $options = Json::encode([
            'params' => $protectedParamsResult,
        ]);

        \Yii::$app->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.classes.ProtectedParams = sx.classes.Component.extend({

                _init: function()
                {},

                _onDomReady: function()
                {
                    var self = this;
                    $(document).on('pjax:complete', function() {
                        self.update();
                    });

                    self.update();
                },

                update: function()
                {
                    _.each(this.get('params'), function(id, value)
                    {
                        $(".field-" + id).hide();
                    });
                },

                _onWindowReady: function()
                {}
            });

            new sx.classes.ProtectedParams($options);
        })(sx, sx.$, sx._);
JS
);
        return \Yii::$app->getView()->renderFile($this->configFormFile(), [
            'model'             => $this,
            'protectedParams'   => $protectedParams
        ]);
    }

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function getDescriptorConfig()
    {
        return [];
    }

    /**
     * Загрузка настроек по умолчанию
     * TODO: добавить кэш
     * TODO: переписать, чтобы настройки могли храниться не только в базе (пока так)
     * @return $this
     */
    public function loadDefaultSettings()
    {

        try
        {
            /**
             * @var $settings Settings
             */
            $settings = $this->fetchDefaultSettings();

            if ($settings)
            {
                $values = (array) $settings->getMultiFieldValue('value');
                $this->setAttributes($values);
            }

        } catch (\Exception $e)
        {
            \Yii::error('Cms component error load defaul settings: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function saveDefaultSettings()
    {
        $settings = $this->fetchDefaultSettings();
        if (!$settings)
        {
            $settings = new Settings([
                'component' => $this->className()
            ]);
        }

        $settings->setCurrentLang(null);
        $settings->setCurrentSite(null);

        if ($this->_currentLang)
        {
            $settings->setCurrentLang($this->_currentLang);
        }

        if ($this->_currentSite)
        {
            $settings->setCurrentSite($this->_currentSite);
        }

        $settings->setMultiFieldValue('value', $this->attributes);

        return $settings->save(false);
    }




    /**
     * @var null|Site|int|string
     */
    protected $_currentSite = null;

    /**
     * @var null|Lang|string
     */
    protected $_currentLang = null;


    /**
     * @param Site|int|string $site
     * @return $this
     */
    public function setCurrentSite($site)
    {
        $this->_currentSite = $site;
        return $this;
    }
    /**
     * @param Lang|string $lang
     * @return $this
     */
    public function setCurrentLang($lang)
    {
        $this->_currentLang = $lang;
        return $this;
    }



    /**
     * Сбросить настройки по умаолчанию
     * @return Settings
     */
    public function resetDefaultSettings()
    {
        if ($defaultSettings = $this->fetchDefaultSettings())
        {
            return $defaultSettings->delete();
        }

        return true;
    }

    /**
     * Спарсить настройки по умолчанию
     * @return Settings
     */
    public function fetchDefaultSettings()
    {
        //return Settings::find()->where(['component' => $this->className()])->one();

        $dependency = new \yii\caching\DbDependency(['sql' => 'SELECT MAX(updated_at) FROM ' . Settings::tableName()]);

        return Settings::getDb()->cache(function ($db) {
            return Settings::find()->where(['component' => $this->className()])->one();
        }, 3600*3, $dependency);
    }


}