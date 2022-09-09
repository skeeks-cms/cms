<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\propertyTypes;

use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\widgets\AjaxSelect;
use skeeks\cms\widgets\Select;
use yii\bootstrap\Alert;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/**
 * Class PropertyTypeList
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeList extends PropertyType
{
    public $enumRoute = 'cms/admin-cms-content-property-enum';

    public $code = self::CODE_LIST;
    public $name = "";

    const FIELD_ELEMENT_SELECT = "select";
    const FIELD_ELEMENT_SELECT_MULTI = "selectMulti";
    const FIELD_ELEMENT_LISTBOX = "listbox";
    const FIELD_ELEMENT_LISTBOX_MULTI = "listboxmulti";

    const FIELD_ELEMENT_RADIO_LIST = "radioList";
    const FIELD_ELEMENT_CHECKBOX_LIST = "checkbox";

    const FIELD_ELEMENT_SELECT_DIALOG = "selectDialog";
    const FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE = "selectDialogMulti";

    public static function fieldElements()
    {
        return [
            self::FIELD_ELEMENT_SELECT                 => \Yii::t('skeeks/cms', 'Выпадающий список'),
            self::FIELD_ELEMENT_SELECT_MULTI           => \Yii::t('skeeks/cms', 'Выпадающий список (возможность выбора нескольких значений)'),
            //self::FIELD_ELEMENT_RADIO_LIST             => \Yii::t('skeeks/cms', 'Radio Buttons (selecting one value)'),
            //self::FIELD_ELEMENT_CHECKBOX_LIST          => \Yii::t('skeeks/cms', 'Checkbox List'),
            //self::FIELD_ELEMENT_LISTBOX                => \Yii::t('skeeks/cms', 'ListBox'),
            //self::FIELD_ELEMENT_LISTBOX_MULTI          => \Yii::t('skeeks/cms', 'ListBox Multi'),
            /*self::FIELD_ELEMENT_SELECT_DIALOG          => \Yii::t('skeeks/cms', 'Selection widget in the dialog box'),
            self::FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE => \Yii::t('skeeks/cms',
                'Selection widget in the dialog box (multiple choice)'),*/

        ];
    }

    public $fieldElement = self::FIELD_ELEMENT_SELECT;

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'List');
        }
    }

    /**
     * @return bool
     */
    public function getIsMultiple()
    {
        if (in_array($this->fieldElement, [
            self::FIELD_ELEMENT_SELECT_MULTI,
            self::FIELD_ELEMENT_CHECKBOX_LIST,
            self::FIELD_ELEMENT_LISTBOX_MULTI,
            self::FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE,
        ])) {
            return true;
        }

        return false;
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
                'fieldElement' => \Yii::t('skeeks/cms', 'Element form'),
            ]);
    }

    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
                'fieldElement' => \Yii::t('skeeks/cms', 'Задайте то, как будет происходить выбор значений списка'),
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
                ['fieldElement', 'required'],
                ['fieldElement', 'string'],
                ['fieldElement', 'in', 'range' => array_keys(static::fieldElements())],
            ]);
    }

    /**
     * @return string
     */
    public function renderConfigFormFields(ActiveForm $activeForm)
    {
        $result = $activeForm->fieldSelect($this, 'fieldElement',
            \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList::fieldElements());

        if ($controllerProperty = \Yii::$app->createController($this->enumRoute)[0]) {
            /**
             * @var \skeeks\cms\backend\BackendAction $actionIndex
             * @var \skeeks\cms\backend\BackendAction $actionCreate
             */
            $actionCreate = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
            $actionIndex = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'index');

            if ($this->property->isNewRecord) {
                $result .= Alert::widget([
                    'options' => [
                        'class' => 'alert-info text-center',
                    ],
                    'body'    => \Yii::t('skeeks/cms', 'To start setting up options, save this property.'),
                ]);
            } else {
                
                $result .= Alert::widget([
                    'options' => [
                        'class' => 'alert-default text-center',
                    ],
                    'body'    => \Yii::t('skeeks/cms', 'Опции для этого списка задаются в отдельной вкладке, которая доступна после сохранения этого свойства.'),
                ]);
            }

        }

        return $result;
    }

    protected $_ajaxSelectUrl = null;
    protected $_enumClass = null;

    public function setAjaxSelectUrl($url) {
        $this->_ajaxSelectUrl = $url;
        return $this;
    }

    public function getAjaxSelectUrl()
    {
        if ($this->_ajaxSelectUrl === null) {
            
            $r = new \ReflectionClass($this->property);
            $this->_ajaxSelectUrl = Url::to([
                '/cms/ajax/autocomplete-eav-options', 
                'code' => $this->property->code, 
                'cms_site_id' => \Yii::$app->skeeks->site->id,
                'property_class' => $r->getName(),
                'property_enum_class' => $this->enumClass
            ]);
        }

        return $this->_ajaxSelectUrl;
    }

    public function setEnumClass($class) {
        $this->_enumClass = $class;
        return $this;
    }

    public function getEnumClass()
    {
        if ($this->_enumClass === null) {
            if ($enumClassName = $this->property->relatedPropertyEnumClassName) {
                $this->_enumClass = $enumClassName;
            } else {

                $r = new \ReflectionClass($this->property);
                $enumClassName = $r->getName() . "Enum";
                $this->_enumClass = $enumClassName;
            }
            
        }

        return $this->_enumClass;
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $find = CmsContentPropertyEnum::find()->andWhere(['property_id' => $this->property->id]);

        if (in_array($this->fieldElement, [
                self::FIELD_ELEMENT_SELECT,
                self::FIELD_ELEMENT_RADIO_LIST,

            ])) {

            $config = [];
            if ($this->property->is_required) {
                $config['allowDeselect'] = false;
            } else {
                $config['allowDeselect'] = true;
            }

            //echo $this->property->relatedPropertiesModel->getAttribute($this->property->code);
            $field->widget(
                AjaxSelect::class, [
                    'ajaxUrl' => $this->getAjaxSelectUrl(),
                    'valueCallback' => function($value) {
                        $class = $this->getEnumClass();
                        return \yii\helpers\ArrayHelper::map($class::find()->where(['id' => $value])->all(), 'id', 'value');
                    },
                ]
            );
        } else {
            $field->widget(
                AjaxSelect::class, [
                    'multiple' => true,
                    'ajaxUrl' => $this->getAjaxSelectUrl(),
                    'valueCallback' => function($value) {
                        $class = $this->getEnumClass();
                        return \yii\helpers\ArrayHelper::map($class::find()->where(['id' => $value])->all(), 'id', 'value');
                    },
                ]
            );
        }

        return $field;
    }

    /**
     * @deprecated
     * @return \yii\widgets\ActiveField
     */
    public function _renderForActiveFormOld()
    {
        $field = parent::renderForActiveForm();

        $find = CmsContentPropertyEnum::find()->andWhere(['property_id' => $this->property->id]);

        if ($this->fieldElement == self::FIELD_ELEMENT_SELECT) {

            $config = [];
            if ($this->property->is_required) {
                $config['allowDeselect'] = false;
            } else {
                $config['allowDeselect'] = true;
            }

            //echo $this->property->relatedPropertiesModel->getAttribute($this->property->code);
            $field->widget(
                AjaxSelect::class, [
                    'ajaxUrl' => $this->getAjaxSelectUrl(),
                    'valueCallback' => function($value) {
                        return \yii\helpers\ArrayHelper::map(CmsContentPropertyEnum::find()->where(['id' => $value])->all(), 'id', 'value');
                    },
                ]
            );

        } else {
            if ($this->fieldElement == self::FIELD_ELEMENT_SELECT_MULTI) {

                $field->widget(
                AjaxSelect::class, [
                    'multiple' => true,
                    'ajaxUrl' => $this->getAjaxSelectUrl(),
                    'valueCallback' => function($value) {
                        return \yii\helpers\ArrayHelper::map(CmsContentPropertyEnum::find()->where(['id' => $value])->all(), 'id', 'value');
                    },
                ]
            );

            } else {
                if ($this->fieldElement == self::FIELD_ELEMENT_RADIO_LIST) {
                    $field = parent::renderForActiveForm();
                    $field->radioList(ArrayHelper::map($this->property->enums, 'id', 'value'));

                } else {
                    if ($this->fieldElement == self::FIELD_ELEMENT_CHECKBOX_LIST) {
                        $field = parent::renderForActiveForm();
                        $field->checkboxList(ArrayHelper::map($this->property->enums, 'id', 'value'));

                    } else {
                        if ($this->fieldElement == self::FIELD_ELEMENT_LISTBOX_MULTI) {
                            $field = parent::renderForActiveForm();
                            $field->listBox(ArrayHelper::map($this->property->enums, 'id', 'value'), [
                                'size'     => 5,
                                'multiple' => 'multiple',
                            ]);
                        } else {
                            if ($this->fieldElement == self::FIELD_ELEMENT_LISTBOX) {
                                $field = parent::renderForActiveForm();
                                $field->listBox(ArrayHelper::map($this->property->enums, 'id', 'value'), [
                                    'size' => 1,
                                ]);
                            } else {
                                if ($this->fieldElement == self::FIELD_ELEMENT_SELECT_DIALOG) {
                                    $field = parent::renderForActiveForm();
                                    $field->widget(
                                        \skeeks\cms\backend\widgets\SelectModelDialogWidget::class,
                                        [
                                            'modelClassName' => \skeeks\cms\models\CmsContentPropertyEnum::class,
                                            'dialogRoute'    => [
                                                '/cms/admin-cms-content-property-enum',
                                                'CmsContentPropertyEnum' => [
                                                    'property_id' => $this->property->id,
                                                ],
                                            ],
                                        ]
                                    );
                                } else {
                                    if ($this->fieldElement == self::FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE) {
                                        $field = parent::renderForActiveForm();
                                        $field->widget(
                                            \skeeks\cms\backend\widgets\SelectModelDialogWidget::class,
                                            [
                                                'modelClassName' => \skeeks\cms\models\CmsContentPropertyEnum::class,
                                                'dialogRoute'    => [
                                                    '/cms/admin-cms-content-property-enum',
                                                    'CmsContentPropertyEnum' => [
                                                        'property_id' => $this->property->id,
                                                    ],
                                                ],
                                                'multiple'       => true,
                                            ]
                                        );
                                    } else {
                                        $field = $this->activeForm->fieldSelect(
                                            $this->property->relatedPropertiesModel,
                                            $this->property->code,
                                            ArrayHelper::map($this->property->enums, 'id', 'value'),
                                            []
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $field;
    }

    /**
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules()
    {
        if ($this->isMultiple) {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'safe');
        } else {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'integer');
        }

        if ($this->property->isRequired) {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'required');
        }

        return $this;
    }


    
    
    /**
     * @return string
     */
    public function getAsText()
    {
        $value = $this->property->relatedPropertiesModel->getAttribute($this->property->code);

        if ($this->isMultiple) {

            $result = [];

            $data = $this->property->getEnums()->andWhere(['id' => $value])->select(['code', 'value'])->limit(10)->asArray()->all();
            if ($data) {
                $result = ArrayHelper::map($data, 'code', 'value');
            }


            return implode(", ", $result);

        } else {
            if (isset(self::$propertyEnumValues[$this->property->id][$value])) {
                return self::$propertyEnumValues[$this->property->id][$value];
            } else {
                if ($enum = $this->property->getEnums()->andWhere(['id' => $value])->one()) {
                    if ($enum) {
                        self::$propertyEnumValues[$this->property->id][$value] = $enum->value;
                        return self::$propertyEnumValues[$this->property->id][$value];
                    }
                }

                self::$propertyEnumValues[$this->property->id][$value] = "";
                return "";
            }

            /*if ($this->property->enums) {
                $enums = (array)$this->property->enums;

                foreach ($enums as $enum) {
                    if ($enum->id == $value) {
                        return $enum->value;
                    }
                }
            }*/

            //return "";
        }
    }

    static public $propertyEnumValues = [];
}