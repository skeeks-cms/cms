<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\models\CmsTreeTypeProperty;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsContentPropertyEnumController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Managing property values');
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsContentPropertyEnum::class;

        //$this->generateAccessActions = false;
        /*$this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };*/

        parent::init();
    }

    static public function initGridQuery($query)
    {
        $subQuery = CmsContentElementProperty::find()->select([new Expression("count(1)")])->where([
            'value_enum_id' => new Expression(CmsContentPropertyEnum::tableName().".id")
        ]);

        if (!\Yii::$app->skeeks->site->is_default) {
            $query->andWhere(['property.cms_site_id' => \Yii::$app->skeeks->site->id]);
        } else {
            $query->andWhere([
                'or',
                ['property.cms_site_id' => \Yii::$app->skeeks->site->id],
                ['property.cms_site_id' => null]
            ]);
        }

        $query->joinWith("property as property");
        $query->groupBy(CmsContentPropertyEnum::tableName().".id");
        $query->select([
            CmsContentPropertyEnum::tableName().'.*',
            'countElementProperties' => $subQuery,
        ]);
    }
    
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'filters' => [
                    'visibleFilters' => [
                        'value',
                        'property_id',
                    ],
                ],
                'grid'    => [
                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $dataProvider = $e->sender->dataProvider;

                        //$query->joinWith('elementProperties as elementProperties');
                        self::initGridQuery($query);
                    },
                    
                    'sortAttributes' => [
                        'countElementProperties' => [
                            'asc'     => ['countElementProperties' => SORT_ASC],
                            'desc'    => ['countElementProperties' => SORT_DESC],
                            'label'   => \Yii::t('skeeks/cms', 'Number of partitions where the property is filled'),
                            'default' => SORT_ASC,
                        ],
                    ],
                    
                    'defaultOrder'   => [
                        //'def' => SORT_DESC,
                        'priority' => SORT_ASC,
                    ],
                    
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'value',
                        'property_id',
                        'code',
                        'priority',
                        'countElementProperties',
                    ],
                    'columns' => [
                        'value' => [
                            'attribute' => "value",
                            'format'    => "raw",
                            'value'     => function (CmsContentPropertyEnum $model) {
                                return Html::a($model->value, "#", [
                                    'class' => "sx-trigger-action",
                                ]);
                            },
                        ],
                        
                        'countElementProperties' => [
                            'attribute' => 'countElementProperties',
                            'format'    => 'raw',
                            'label'     => \Yii::t('skeeks/cms', 'Где заполнена опция'),
                            'value'     => function (CmsContentPropertyEnum $model) {
                                return isset($model->raw_row['countElementProperties']) ? $model->raw_row['countElementProperties'] : "";
                            },
                        ],
                    ]
                ],
            ],
            'create' => [
                'fields' => [$this, 'updateFields'],
            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
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

        //Используется в создании свойств прям со страницы заполнения товара/элемента
        if ($property_id = \Yii::$app->request->get("property_id")) {
            $model->property_id = $property_id;
        }

        $qProperty = \skeeks\cms\models\CmsContentProperty::find();

        if (!\Yii::$app->skeeks->site->is_default) {
            $qProperty->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
        } else {
            $qProperty->andWhere([
                'or',
                [CmsContentProperty::tableName() . '.cms_site_id' => \Yii::$app->skeeks->site->id],
                [CmsContentProperty::tableName() . '.cms_site_id' => null]
            ]);
        }

        if ($model->property_id && $model->isNewRecord) {
            $result = [
                [
                    'class' => HtmlBlock::class,
                    'content' => "<div style='display: none;'>"
                ],
                'property_id',
                [
                    'class' => HtmlBlock::class,
                    'content' => "</div>"
                ],
                'value',
                'code',
                'priority' => [
                    'class' => NumberField::class,
                ],
            ];
        } else {

            $result = [

                'property_id' => [
                    'class' => SelectField::class,
                    'items' => \yii\helpers\ArrayHelper::map(
                        $qProperty->all(),
                        "id",
                        "name"
                    )
                ],
                'value',
                'code',
                'priority' => [
                    'class' => NumberField::class,
                ],
            ];
        }

        return $result;
    }
}
