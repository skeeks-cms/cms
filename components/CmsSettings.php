<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.07.2015
 */
namespace skeeks\cms\components;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

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

    public function renderConfigForm(ActiveForm $form)
    {
        echo $form->fieldSet('Безопасность');

        echo $form->fieldSelect($this, 'sessionType', [
                \skeeks\cms\components\CmsSettings::SESSION_FILE    => 'В файлах',
                \skeeks\cms\components\CmsSettings::SESSION_DB      => 'В базе данных',
            ])->hint('Хранилище сессий');

        echo $form->fieldSetEnd();;
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