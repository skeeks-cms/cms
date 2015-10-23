<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\userPropertyTypes;
use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\relatedProperties\PropertyType;
use yii\helpers\ArrayHelper;

/**
 * Class UserPropertyTypeDate
 * @package skeeks\cms\relatedProperties\userPropertyTypes
 */
class UserPropertyTypeDate extends PropertyType
{
    public $code = self::CODE_NUMBER;
    public $name = "";

   /* static public $types = [
        \kartik\datecontrol\DateControl::FORMAT_DATETIME => 'Дата и время',
        \kartik\datecontrol\DateControl::FORMAT_DATE => 'Только дата',
        //\kartik\datecontrol\DateControl::FORMAT_TIME => 'Только время',
    ];*/

    public $type = \kartik\datecontrol\DateControl::FORMAT_DATETIME;

    public function init()
    {
        parent::init();

        if(!$this->name)
        {
            $this->name = \Yii::t('app','Datetime');
        }

    }

    public static function types()
    {
        return [
            \kartik\datecontrol\DateControl::FORMAT_DATETIME => \Yii::t('app','Datetime'),
            \kartik\datecontrol\DateControl::FORMAT_DATE => \Yii::t('app','Only date'),
            //\kartik\datecontrol\DateControl::FORMAT_TIME => 'Только время',
        ];
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'type'  => 'Тип',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['type', 'string'],
            ['type', 'in', 'range' => array_keys(self::types())],
        ]);
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->widget(\kartik\datecontrol\DateControl::classname(), [
            'type' => $this->type,
        ]);

        return $field;
    }


    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views/_formUserPropertyTypeDate.php';
    }
}