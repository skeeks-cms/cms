<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.07.2015
 */
namespace skeeks\cms\components;

use yii\helpers\ArrayHelper;

/**
 * Class CmsSettings
 * @package skeeks\cms\components
 */
class CmsSettings extends \skeeks\cms\base\Component
{
    const SESSION_FILE  = 'file';
    const SESSION_DB    = 'db';

    public $sessionType = self::SESSION_FILE;

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Дополнительные настройки CMS',
        ]);
    }

    /**
     * Файл с формой настроек, по умолчанию
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'cms/_settings.php';
    }


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['sessionType'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'sessionType'               => 'Где хранить сессии',
        ]);
    }
}