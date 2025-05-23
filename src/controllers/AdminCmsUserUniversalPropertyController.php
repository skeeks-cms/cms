<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\measure\models\CmsMeasure;
use skeeks\cms\models\CmsUserUniversalProperty;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsUserUniversalPropertyController extends BackendModelStandartController
{
    public $notSubmitParam = 'sx-not-submit';

    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'User control properties');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsUserUniversalProperty::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();

    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [

            'index' => [
                'filters' => [
                    'visibleFilters' => [
                        'name',
                    ],
                ],

                'grid' => [
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'priority',
                        'hint',
                        'is_active',
                    ],
                    'columns'        => [
                        'custom' => [
                            'attribute' => "name",
                            'format'    => "raw",
                            'value'     => function (CmsUserUniversalProperty $model) {
                                return Html::a($model->asText, "#", [
                                        'class' => "sx-trigger-action",
                                    ])."<br />".Html::tag('small', $model->handler->name);
                            },
                        ],

                        'is_active' => [
                            'class' => BooleanColumn::class,
                        ],
                    ],
                ],
            ],

            'create' => [
                'fields' => [$this, 'updateFields'],
                'size'   => BackendAction::SIZE_SMALL,

                'on beforeSave' => function (Event $e) {
                    $model = $e->sender->model;

                    $handler = $model->handler;
                    if ($handler) {
                        if ($post = \Yii::$app->request->post()) {
                            $handler->load($post);
                        }
                        $model->component_settings = $handler->toArray();
                    }
                },

            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
                'size'   => BackendAction::SIZE_SMALL,

                'on beforeSave' => function (Event $e) {
                    $model = $e->sender->model;


                    $handler = $model->handler;
                    if ($handler) {
                        if ($post = \Yii::$app->request->post()) {
                            $handler->load($post);
                        }
                        $model->component_settings = $handler->toArray();
                    }

                },
            ],

            'enums' => [
                'class'           => BackendGridModelRelatedAction::class,
                'accessCallback'  => true,
                'name'            => "Элементы списка",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-user-universal-property-enum",
                'relation'        => ['property_id' => 'id'],
                'priority'        => 150,

                'on gridInit' => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'property_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },

                'accessCallback' => function (BackendModelAction $action) {

                    /**
                     * @var $model CmsContentProperty
                     */
                    $model = $action->model;

                    if (!$model) {
                        return false;
                    }

                    if ($model->property_type != PropertyType::CODE_LIST) {
                        return false;
                    }

                    return true;
                },
            ],

        ]);
    }


    public function updateFields($action)
    {
        /**
         * @var $model CmsTreeTypeProperty
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $isChange = true;
        $changeOptions = [];
        $message = 'От этого зависит как будет показываться свойство в форме редактирования.';

        if (!$model->isNewRecord) {
            if ($model->property_type == PropertyType::CODE_LIST) {
                if ($model->getEnums()->one()) {
                    $isChange = false;
                    $message = 'Нельзя менять тип характеристики, потому что у нее уже созданы опции.';
                }
            }
        }
        if (!$isChange) {
            $changeOptions = [
                'disabled' => "disabled",
            ];
        }

        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Basic settings'),
                'fields' => [
                    'name',

                    'component' => [
                        'class'          => SelectField::class,
                        'elementOptions' => ArrayHelper::merge([
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ], $changeOptions),
                        'items'          => function () {
                            return array_merge(['' => ' — '], \Yii::$app->cms->relatedHandlersDataForSelect);
                        },
                        'hint'           => $message,
                    ],

                    'cms_measure_code' => [
                        'class' => SelectField::class,
                        /*'elementOptions' => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],*/
                        'items' => function () {
                            return ArrayHelper::map(
                                CmsMeasure::find()->all(),
                                'code',
                                'asShortText'
                            );
                        },
                    ],


                    [
                        'class'           => HtmlBlock::class,
                        'on beforeRender' => function (Event $e) use ($model) {
                            /**
                             * @var $formElement Element
                             */
                            $formElement = $e->sender;
                            $formElement->activeForm;

                            $handler = $model->handler;

                            if ($handler) {
                                if ($post = \Yii::$app->request->post()) {
                                    $handler->load($post);
                                }
                                if ($handler instanceof \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList) {
                                    $handler->enumRoute = 'cms/admin-cms-user-universal-property-enum';
                                }

                                $content = $handler->renderConfigFormFields($formElement->activeForm);

                                if ($content) {
                                    $formElement->content = \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('skeeks/cms', 'Settings')]).$content;
                                }
                            }
                        },
                    ],
                ],
            ],

            'captions' => [
                'class'          => FieldSet::class,
                'name'           => \Yii::t('skeeks/cms', 'Additionally'),
                'elementOptions' => ['isOpen' => false],
                'fields'         => [

                    'is_active' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],

                    'is_required' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'code',
                    'hint',
                    'priority'    => [
                        'class' => NumberField::class,
                    ],

                ],
            ],
        ];
    }
}
