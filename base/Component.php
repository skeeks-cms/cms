<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\models\CmsComponentSettings;
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
        \Yii::trace('Cms component init: ' . $this->className());
        parent::init();
    }

    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
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
     * Отрисовка формы настроек.
     * @return string
     */
    public function renderConfigForm()
    {
        return \Yii::$app->getView()->renderFile($this->configFormFile(),
        [
            'model'             => $this,
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
}