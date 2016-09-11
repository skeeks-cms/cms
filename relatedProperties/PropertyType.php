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
use skeeks\cms\base\ConfigFormInterface;
use skeeks\cms\components\Cms;
use skeeks\cms\relatedProperties\models\RelatedElementModel;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * @property bool $isMultiple
 *
 * Class PropertyType
 * @package skeeks\cms\relatedProperties
 */
abstract class PropertyType extends Model implements ConfigFormInterface
{
    /**
     * @param ActiveForm $form
     */
    public function renderConfigForm(ActiveForm $form)
    {}

    const CODE_STRING   = 'S';
    const CODE_NUMBER   = 'N';
    const CODE_LIST     = 'L';
    const CODE_FILE     = 'F';
    const CODE_TREE     = 'T';
    const CODE_ELEMENT  = 'E';

    public $id;
    /**
     * @var код типа свойства (логика приложения)
     */
    public $code;
    /**
     * @var Название свойства
     */
    public $name;

    /**
     * Это модель со свойствами
     * Для полей формы используется $this->model->relatedPropertiesModel
     *
     * @var \skeeks\cms\relatedProperties\models\RelatedElementModel
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

    /**
     * @return bool
     */
    public function getIsMultiple()
    {
        return false;
    }

    /**
     * TODO: is depricated @varsion > 3.0.2
     * @return string
     */
    public function getMultiple()
    {
        return $this->isMultiple ? Cms::BOOL_Y : Cms::BOOL_N;
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = $this->activeForm->field($this->model->relatedPropertiesModel, $this->property->code);

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
            $label = $this->property->name;
            $field->label($label);
        } else
        {
            $field->label(false);
        }

        return $field;
    }

    /**
     * @varsion > 3.0.2
     * @param RelatedPropertiesModel $relatedPropertiesModel
     *
     * @return $this
     */
    public function addRulesToRelatedPropertiesModel(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $relatedPropertiesModel->addRule($this->property->code, 'safe');
        return $this;
    }
}
