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
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/**
 * @property bool $isMultiple
 * @property mixed $defaultValue
 *
 * @property string $asText
 * @property string $asHtml
 *
 * Class PropertyType
 * @package skeeks\cms\relatedProperties
 */
abstract class PropertyType extends Model implements ConfigFormInterface
{
    /**
     * @var string
     */
    public $id;

    /**
     * The name of the handler
     * @var string
     */
    public $name;

    /**
     * Object properties is bound to the current handler
     * @var RelatedPropertyModel
     */
    public $property;

    /**
     * Object form which will be completed item
     * @var ActiveForm
     */
    public $activeForm;

    /**
     * The configuration form for the current state of the component settings
     * @param ActiveForm $form
     */
    public function renderConfigForm(ActiveForm $form)
    {
    }

    /**
     * From the result of this function will depend on how the property values are stored in the database
     * @return bool
     */
    public function getIsMultiple()
    {
        return false;
    }

    /**
     * Drawing form element
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = $this->activeForm->field($this->property->relatedPropertiesModel, $this->property->code);

        if (!$field) {
            return '';
        }

        return $field;
    }

    /**
     * Adding validation rules to the object RelatedPropertiesModel
     *
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules()
    {
        $this->property->relatedPropertiesModel->addRule($this->property->code, 'safe');

        if ($this->property->isRequired) {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'required');
        }

        return $this;
    }

    /**
     * The default value for this property
     *
     * @varsion > 3.0.2
     *
     * @return null
     */
    public function getDefaultValue()
    {
        return null;
    }

    /**
     * @return string
     * @depricated
     */
    public function getStringValue()
    {
        $value = $this->property->relatedPropertiesModel->getAttribute($this->property->code);

        if (is_array($value)) {
            return Json::encode($value);
        } else {
            return (string)$value;
        }
    }

    /**
     * @return string
     */
    public function getAsText()
    {
        return $this->stringValue;
    }

    /**
     * @return string
     */
    public function getAsHtml()
    {
        return $this->asText;
    }

    /**
     * Conversion property value received from the database
     *
     * @param mixed $valueFromDb
     *
     * @return mixed
     */
    public function initValue($valueFromDb)
    {
        /*
            $valueFromDb          = unserialize($valueFromDb);
        */

        return $valueFromDb;
    }

    /**
     * Converting the property value before saving to database
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function beforeSaveValue($value)
    {
        /*
            $value      = serialize($value);
        */
        return $value;
    }

    /**
     * Fires before the removal of the property value of the element base
     *
     * @return $this
     */
    public function beforeDeleteValue()
    {
        /*$value        = $this->property->relatedPropertiesModel->getAttribute($this->property->code);
        $valueToDb      = serialize($value);
        $this->property->relatedPropertiesModel->setAttribute($this->property->code, $valueToDb);*/
        return $this;
    }







    /**
     * TODO: It may be deprecated
     */


    /**
     * TODO: It may be deprecated @version > 3.0.2
     */
    const CODE_STRING = 'S';
    const CODE_NUMBER = 'N';
    const CODE_FILE = 'F';

    const CODE_STORAGE_FILE = 'A';

    const CODE_LIST = 'L';
    const CODE_TREE = 'T';
    const CODE_ELEMENT = 'E';
    const CODE_BOOL = 'B';

    const CODE_RANGE = 'R';


    /**
     * TODO: It may be deprecated @version > 3.0.2
     * @var код типа свойства (логика приложения)
     */
    public $code;

    /**
     * TODO: is deprecated @version > 3.0.2
     * @return string
     */
    public function getMultiple()
    {
        return $this->isMultiple ? Cms::BOOL_Y : Cms::BOOL_N;
    }

}
