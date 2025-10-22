<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\UserColumnData;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsFaq;
use skeeks\cms\models\CmsTree;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget;
use skeeks\yii2\ckeditor\CKEditorWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\WidgetField;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsFaqController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Вопрос/Ответ");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsFaq::class;

        $this->generateAccessActions = true;
        /*$this->permissionName = CmsManager::PERMISSION_ADMIN_ACCESS;*/

        /*$this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        };*/


        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [
                'grid' => [
                    'defaultOrder' => [
                        'created_at' => SORT_DESC,
                    ],
                    /*'sortAttributes' => [
                        'countProducts'   => [
                            'asc'     => ['countProducts' => SORT_ASC],
                            'desc'    => ['countProducts' => SORT_DESC],
                            'label'   => 'Количество товаров',
                            'default' => SORT_ASC,
                        ],
                    ],*/

                    'visibleColumns' => [
                        'checkbox',
                        'actions',

                        'name',
                        'trees',
                        'contentElements',

                        'is_active',
                    ],

                    'columns' => [

                        'name'       => [
                            'class' => DefaultActionColumn::class,
                        ],
                        'created_at' => [
                            'class' => DateTimeColumnData::class,
                        ],

                        'created_by' => [
                            'class' => UserColumnData::class,
                        ],


                        'is_active' => [
                            'class' => BooleanColumn::class,
                        ],

                        'trees' => [
                            'attribute' => 'trees',
                            'format'    => 'raw',
                            'value'     => function (CmsFaq $model) {

                                $data = [];
                                if ($model->trees) {
                                    foreach ($model->trees as $tree)
                                    {
                                        $data[] = Html::a($tree->asText, $tree->url, ['target' => '_blank', 'data-pjax' => 0]);
                                    }
                                }

                                return implode("<br>", $data);

                            },
                        ],
                        'contentElements' => [
                            'attribute' => 'contentElements',
                            'format'    => 'raw',
                            'value'     => function (CmsFaq $model) {

                                $data = [];
                                if ($model->contentElements) {
                                    foreach ($model->contentElements as $element)
                                    {
                                        $data[] = Html::a($element->asText, $element->url, ['target' => '_blank', 'data-pjax' => 0]);
                                    }
                                }

                                return implode("<br>", $data);

                            },
                        ],

                    ],
                ],
            ],
            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],

            "log" => [
                'class'          => BackendModelLogAction::class,
                "generateAccess" => true,
            ],

        ]);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsFaq
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [

                    'is_active'       => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'name',
                    'response'        => [
                        'class'       => WidgetField::class,
                        'widgetClass' => CKEditorWidget::class,
                    ],
                    'trees'           => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => SelectTreeInputWidget::class,
                        'widgetConfig' => [
                            'multiple'                  => true,
                            'isAllowNodeSelectCallback' => function ($tree) {
                                /*if (in_array($tree->id, $childrents)) {
                                    return false;
                                }*/

                                return true;
                            },
                            'treeWidgetOptions'         => [
                                'models' => CmsTree::findRoots()->cmsSite()->all(),
                            ],
                        ],
                    ],
                    'contentElements' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'multiple'   => true,
                            'modelClass' => CmsContentElement::class,
                        ],
                    ],
                    'priority'        => [
                        'class' => NumberField::class,
                    ],
                ],
            ],


        ];
    }



    /**
     * @return RequestResponse
     */
    public function actionJoinTree()
    {
        $rr = new RequestResponse();

        $treeId = \Yii::$app->request->post("tree_id");
        $pk = \Yii::$app->request->get("pk");

        if ($treeId && $pk) {

            /**
             * @var $faq CmsFaq
             */
            $faq = CmsFaq::find()->andWhere(['id' => $pk])->one();

            if ($faq) {

                $newTreeIds = [];
                foreach ($faq->trees as $tree)
                {
                    $newTreeIds[] = $tree->id;
                }
                $newTreeIds[] = $treeId;
                $faq->trees = $newTreeIds;

                if (!$faq->save()) {
                    print_r($property->errors);die;
                    $rr->success = false;
                    $rr->message = print_r($property->errors, true);
                    return $rr;
                }

                $rr->success = true;
                $rr->message = "Раздел добавлен";

            }

        }

        return $rr;
    }
}
