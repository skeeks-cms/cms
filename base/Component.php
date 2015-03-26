<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
namespace skeeks\cms\base;
use yii\base\Component as YiiComponent;
use yii\base\Model;

/**
 * Class Component
 * @package skeeks\cms\base
 */
class Component extends Model
{
    public function init()
    {
        parent::init();
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
}