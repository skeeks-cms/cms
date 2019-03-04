<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\propertyTypes;

use skeeks\cms\backend\widgets\ModalPermissionWidget;
use skeeks\cms\relatedProperties\PropertyType;
use yii\bootstrap\Alert;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * Class PropertyTypeList
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeList extends PropertyType
{
    public $enumRoute = 'cms/admin-cms-content-property-enum';
    public $enumClass = '\skeeks\cms\models\CmsContentPropertyEnum';

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
            self::FIELD_ELEMENT_SELECT                 => \Yii::t('skeeks/cms', 'Combobox').' (select)',
            self::FIELD_ELEMENT_SELECT_MULTI           => \Yii::t('skeeks/cms', 'Combobox').' (select multiple)',
            self::FIELD_ELEMENT_RADIO_LIST             => \Yii::t('skeeks/cms', 'Radio Buttons (selecting one value)'),
            self::FIELD_ELEMENT_CHECKBOX_LIST          => \Yii::t('skeeks/cms', 'Checkbox List'),
            self::FIELD_ELEMENT_LISTBOX                => \Yii::t('skeeks/cms', 'ListBox'),
            self::FIELD_ELEMENT_LISTBOX_MULTI          => \Yii::t('skeeks/cms', 'ListBox Multi'),
            self::FIELD_ELEMENT_SELECT_DIALOG          => \Yii::t('skeeks/cms', 'Selection widget in the dialog box'),
            self::FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE => \Yii::t('skeeks/cms',
                'Selection widget in the dialog box (multiple choice)'),

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
        return array_merge(parent::attributeLabels(),
            [
                'fieldElement' => \Yii::t('skeeks/cms', 'Element form'),
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
    public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->fieldSelect($this, 'fieldElement',
            \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList::fieldElements());

        if ($controllerProperty = \Yii::$app->createController($this->enumRoute)[0]) {
            /**
             * @var \skeeks\cms\backend\BackendAction $actionIndex
             * @var \skeeks\cms\backend\BackendAction $actionCreate
             */
            $actionCreate = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
            $actionIndex = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'index');

            if ($this->property->isNewRecord) {
                echo Alert::widget([
                    'options' => [
                        'class' => 'alert-info',
                    ],
                    'body'    => \Yii::t('skeeks/cms', 'To start setting up options, save this property.'),
                ]);
            } else {
                if ($actionIndex) {
                    $pjax = \yii\widgets\Pjax::begin();

                    echo "<div class='row'><div class='col-md-6'> ";

                    if ($actionCreate) {
                        $actionCreate->url = \yii\helpers\ArrayHelper::merge($actionCreate->urlData, [
                            'property_id' => $this->property->id,
                        ]);


                        echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                            'actions'         => ['create' => $actionCreate],
                            'clientOptions'   => ['pjax-id' => $pjax->id],
                            'isOpenNewWindow' => true,
                            'tag'             => 'div',
                            'itemWrapperTag'  => 'span',
                            'itemTag'         => 'button',
                            'itemOptions'     => ['class' => 'btn btn-default'],
                            'options'         => ['class' => 'sx-controll-actions'],
                        ]);

                    }

                    echo '</div><div class="col-md-6"><div class="pull-right">';
                    if (\Yii::$app->user->can('rbac/admin-permission') && $controllerProperty instanceof \skeeks\cms\IHasPermissions) {
                        echo ModalPermissionWidget::widget([
                            'controller' => $controllerProperty,
                        ]);
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';

                    $enumClass = $this->enumClass;
                    $query = $enumClass::find()->orderBy(['priority' => SORT_ASC]);
                    $query->andWhere(['property_id' => $this->property->id]);

                    echo \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
                        'dataProvider'    => new \yii\data\ActiveDataProvider([
                            'query' => $query,
                        ]),
                        'settingsData'    => [
                            'namespace' => \Yii::$app->controller->uniqueId."__".$this->property->id,
                        ],
                        'adminController' => $controllerProperty,
                        'isOpenNewWindow' => true,
                        //'filterModel'       => $searchModel,
                        'autoColumns'     => false,
                        'pjax'            => $pjax,
                        'columns'         => [
                            [
                                'attribute'     => 'id',
                                'enableSorting' => false,
                            ],

                            [
                                'attribute'     => 'code',
                                'enableSorting' => false,
                            ],

                            [
                                'attribute'     => 'value',
                                'enableSorting' => false,
                            ],

                            [
                                'attribute'     => 'priority',
                                'enableSorting' => false,
                            ],

                            [
                                'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                                'attribute'     => 'def',
                                'enableSorting' => false,
                            ],
                        ],
                    ]);

                    \yii\widgets\Pjax::end();
                }
            }

        }

        /*echo \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
            'label'             => \Yii::t('skeeks/cms',"Values for list"),
            'hint'              => \Yii::t('skeeks/cms',"You can snap to the element number of properties, and set the value to them"),
            'parentModel'       => $this->property,
            'relation'          => [
                'property_id' => 'id'
            ],

            'controllerRoute'   => $this->enumRoute,
            'gridViewOptions'   => [
                'sortable' => true,
                'columns' => [
                    [
                        'attribute'     => 'id',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'code',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'value',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'priority',
                        'enableSorting' => false
                    ],

                    [
                        'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                        'attribute'     => 'def',
                        'enableSorting' => false
                    ],
                ],
            ],
        ]);*/
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = '';

        if ($this->fieldElement == self::FIELD_ELEMENT_SELECT) {
            $field = $this->activeForm->fieldSelect(
                $this->property->relatedPropertiesModel,
                $this->property->code,
                ArrayHelper::map($this->property->enums, 'id', 'value'),
                []
            );
        } else {
            if ($this->fieldElement == self::FIELD_ELEMENT_SELECT_MULTI) {
                $field = $this->activeForm->fieldSelectMulti(
                    $this->property->relatedPropertiesModel,
                    $this->property->code,
                    ArrayHelper::map($this->property->enums, 'id', 'value'),
                    []
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
            if ($this->property->enums) {
                $result = [];

                foreach ($this->property->enums as $enum) {
                    if (in_array($enum->id, $value)) {
                        $result[$enum->code] = $enum->value;
                    }

                }

                return implode(", ", $result);
            }
        } else {
            if ($this->property->enums) {
                $enums = (array)$this->property->enums;

                foreach ($enums as $enum) {
                    if ($enum->id == $value) {
                        return $enum->value;
                    }
                }
            }

            return "";
        }
    }
}