<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\components\Cms;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\models\CmsSite;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use skeeks\cms\queryfilters\filters\modes\FilterModeEmpty;
use skeeks\cms\queryfilters\filters\modes\FilterModeNotEmpty;
use skeeks\yii2\form\fields\SelectField;
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


                "def-multi" => [
                    'class'        => AdminMultiModelEditAction::className(),
                    "name"         => "По умолчанию",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiDef'],
                    "priority"     => 0,
                ],

                "activate-multi" => [
                    'class'        => AdminMultiModelEditAction::className(),
                    "name"         => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" => [
                    'class'        => AdminMultiModelEditAction::className(),
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
