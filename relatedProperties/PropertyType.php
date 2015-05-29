<?php
/**
 * Базовый тип свойства.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\relatedProperties;
use skeeks\cms\base\Component;
use skeeks\cms\base\widgets\ActiveForm;
use skeeks\cms\relatedProperties\models\RelatedElementModel;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use yii\base\Model;

/**
 * Class PropertyType
 * @package skeeks\cms\relatedProperties
 */
abstract class PropertyType extends Component
{
    const CODE_STRING   = 'S';
    const CODE_NUMBER   = 'N';
    const CODE_LIST     = 'L';
    const CODE_FILE     = 'F';
    const CODE_TREE     = 'T';
    const CODE_ELEMENT  = 'E';

    /**
     * @var код типа свойства (логика приложения)
     */
    public $code;
    /**
     * @var Название свойства
     */
    public $name;
    /**
     * @var bool множественный выбор
     */
    public $multiple    = false;

    /**
     * @var RelatedElementModel
     */
    public $model;
    /**
     * @var RelatedPropertyModel
     */
    public $property;

    /**
     * @var ActiveForm
     */
    public $activeForm;


    public function init()
    {
        //Не загружаем настройки по умолчанию (В родительском классе идет загрузка настроек из базы, тут этого не надо)
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = $this->activeForm->field($this->model, $this->property->code);

        if (!$field)
        {
            return '';
        }

        $this->postFieldRender($field);
        return $field;
    }

    /**
     *
     * Стандартная обработка поля формы.
     *
     * @param \yii\widgets\ActiveField $field
     * @return \yii\widgets\ActiveField
     */
    public function postFieldRender(\yii\widgets\ActiveField $field)
    {
        if ($this->property->hint)
        {
            $field->hint((string) $this->property->hint);
        }

        if ($this->property->name)
        {
            $field->label($this->property->name);
        } else
        {
            $field->label(false);
        }

        return $field;
    }

    /**
     * @return array
     */
    public function getActiveFormConfig()
    {
        return (array) $this->property->component_settings;
    }
}