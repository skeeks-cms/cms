<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelDeleteAction;
use skeeks\cms\backend\actions\BackendModelMultiAction;
use skeeks\cms\backend\actions\BackendModelMultiDeleteAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\components\Cms;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\models\CmsSite;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use skeeks\cms\queryfilters\filters\modes\FilterModeEmpty;
use skeeks\cms\queryfilters\filters\modes\FilterModeNotEmpty;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsSiteController
 * @package skeeks\cms\controllers
 */
class AdminCmsSiteTwoController extends BackendModelStandartController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Site management");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsSite::class;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $bool = [
            'isAllowChangeMode' => false,
            'field'             => [
                'class' => SelectField::class,
                'items' => [
                    'Y' => \Yii::t('yii', 'Yes'),
                    'N' => \Yii::t('yii', 'No'),
                ],
            ],
        ];

        $updateFields = [
            'main' => [
                'class' => FieldSet::class,
                'name' => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'image_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                        'widgetConfig' => [
                            'accept' => 'image/*',
                            'multiple' => false
                        ]
                    ],
                    'name',
                    'code',
                    'active' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_RADIO_LIST,
                        'allowNull' => false,
                        'trueValue' => 'Y',
                        'falseValue' => 'N',
                    ],
                    'def' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_RADIO_LIST,
                        'allowNull' => false,
                        'trueValue' => 'Y',
                        'falseValue' => 'N',
                    ],
                    'description' => [
                        'class' => TextareaField::class
                    ],
                    'server_name',
                    'priority',
                ]
            ],
        ];

        return ArrayHelper::merge(parent::actions(), [

                'index' => [
                    "filters" => [
                        'visibleFilters' => [
                            'id',
                            'name',
                        ],

                        'filtersModel' => [
                            'fields' => [
                                'name'   => [
                                    'isAllowChangeMode' => false,
                                ],
                                'code'   => [
                                    'isAllowChangeMode' => false,
                                ],
                                'active' => $bool,
                                'def'    => $bool,
                                'image_id'    => [
                                    'isAllowChangeMode' => true,
                                    'modes' => [
                                        FilterModeNotEmpty::class,
                                        FilterModeEmpty::class
                                    ]
                                ]
                            ],
                        ],
                    ],

                    "grid" => [
                        'visibleColumns' => [
                            'checkbox',
                            'actions',
                            'id',
                            'image_id',
                            'server_name',
                            'def',
                            'active',
                            'priority',
                            'code',
                            'name',
                        ],
                        'columns'        => [
                            'active' => [
                                'class' => BooleanColumn::class,
                            ],
                            'def'    => [
                                'class' => BooleanColumn::class,
                            ],
                            'image_id'    => [
                                'class' => ImageColumn2::class,
                            ],
                        ],
                    ],
                ],

                "create" => [
                    'fields' => $updateFields
                ],

                "update" => [
                    'fields' => $updateFields
                ],


                "activate-multi" => [
                    'class'        => BackendModelMultiAction::class,
                    "name"         => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" => [
                    'class'        => BackendModelMultiAction::class,
                    "name"         => "Деактивировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiInActivate'],
                ],
            ]
        );
    }

    /**
     * @param $model
     * @param $action
     * @return bool
     */
    public function eachMultiDef($model, $action)
    {
        try {
            $model->def = Cms::BOOL_Y;
            return $model->save(false);
        } catch (\Exception $e) {
            return false;
        }
    }

}
