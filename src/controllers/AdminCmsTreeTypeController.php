<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\models\CmsTreeType;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AdminCmsTreeTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypeController extends BackendModelStandartController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name = "Настройки разделов";
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsTreeType::className();

        $this->generateAccessActions = false;

        parent::init();
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                "filters" => [
                    'visibleFilters' => [
                        'q',
                    ],
                    'filtersModel'   => [
                        'rules'            => [
                            ['q', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                        ],

                        'fields' => [

                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->andWhere([
                                            'or',
                                            ['like', CmsTreeType::tableName().'.name', $e->field->value],
                                            ['like', CmsTreeType::tableName().'.id', $e->field->value],
                                            ['like', CmsTreeType::tableName().'.code', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsTreeType::tableName().'.id']);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                "grid" => [
                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $dataProvider = $e->sender->dataProvider;

                        $query->joinWith('cmsTrees as cmsTrees');
                        $query->groupBy(CmsTreeType::tableName().".id");
                        $query->select([
                            CmsTreeType::tableName().'.*',
                            'countCmsTrees' => new Expression("count(*)"),
                        ]);
                    },

                    'sortAttributes' => [
                        'countCmsTrees' => [
                            'asc'     => ['countCmsTrees' => SORT_ASC],
                            'desc'    => ['countCmsTrees' => SORT_DESC],
                            'label'   => 'Количество разделов',
                            'default' => SORT_ASC,
                        ],
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',

                        'countCmsTrees',
                        'active',
                        'priority',
                    ],

                    'columns' => [
                        'countCmsTrees' => [
                            'attribute' => 'countCmsTrees',
                            'label'     => 'Количество разделов',
                            'value'     => function (CmsTreeType $model) {
                                return $model->raw_row['countCmsTrees'];
                            },
                        ],
                        'active'        => [
                            'class' => BooleanColumn::class,
                        ],
                        'custom'        => [
                            'attribute' => "name",
                            'format'    => "raw",
                            'value'     => function (CmsTreeType $model) {
                                return Html::a($model->asText, "#", [
                                    'class' => "sx-trigger-action",
                                ]);
                            },
                        ],
                    ],
                ],
            ],

            'create' => [
                'fields' => [$this, 'fields'],
            ],
            'update' => [
                'fields' => [$this, 'fields'],
            ],

            "activate-multi" => [
                'class'        => AdminMultiModelEditAction::className(),
                "name"         => "Активировать",
                //"icon"              => "fa fa-trash",
                "eachCallback" => [$this, 'eachMultiActivate'],
            ],

            "inActivate-multi" => [
                'class'        => AdminMultiModelEditAction::className(),
                "name"         => "Деактивировать",
                //"icon"              => "fa fa-trash",
                "eachCallback" => [$this, 'eachMultiInActivate'],
            ],
        ]);
    }

    public function fields()
    {
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'name',
                    'code',
                    'view_file',
                    'active'                     => [
                        'class'      => BoolField::class,
                        'allowNull'  => false,
                        'trueValue'  => "Y",
                        'falseValue' => "N",
                    ],
                    'default_children_tree_type' => [
                        'class' => SelectField::class,
                        'items' => function () {
                            return \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name');
                        },
                    ],
                ],
            ],

            'captions' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Captions'),
                'fields' => [
                    'name_one',
                    'name_meny'
                ],
            ],
        ];
    }

}
