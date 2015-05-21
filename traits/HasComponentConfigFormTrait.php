<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
namespace skeeks\cms\traits;
/**
 *
 * @property string configFormFile
 *
 * Class HasComponentDescriptorTrait
 * @package skeeks\cms\traits
 */
trait HasComponentConfigFormTrait
{
    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '_form.php';
    }

    /**
     * @return bool
     */
    public function existsConfigFormFile()
    {
        return file_exists($this->configFormFile);
    }

    /**
     * Отрисовка формы настроек.
     * @return string
     */
    public function renderConfigForm()
    {
        return \Yii::$app->getView()->renderFile($this->configFormFile,
        [
            'model'             => $this,
        ]);
    }
}