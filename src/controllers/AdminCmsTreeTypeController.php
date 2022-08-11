<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\models\CmsTreeType;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTreeTypeController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Настройки разделов";
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsTreeType::className();

        $this->generateAccessActions = false;

        $this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };

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
                        'is_active',
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
                        'is_active'        => [
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
                'class' => BackendModelMultiActivateAction::class,
            ],

            "inActivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
            ],
        ]);
    }

    public function fields()
    {
        $siteName = \Yii::$app->skeeks->site->name;
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'name',
                    'code',
                    'view_file',
                    'is_active'                     => [
                        'class'      => BoolField::class,
                        'allowNull'  => false,
                    ],
                    'default_children_tree_type' => [
                        'class' => SelectField::class,
                        'items' => function () {
                            return \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name');
                        },
                    ],
                ],
            ],

            'seo' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'SEO'),
                'fields' => [
                    [
                        'class' => HtmlBlock::class,
                        'content' => <<<HTML
<div class="col-12">
<div class="alert alert-default" style="margin-top: 10px;">
    <p>
    Пример meta title:<br />
    <b>{=section.seoName} купить в интернет-магазине, цены от {=minMoney} - {=siteName}</b>
    </p>
    <p>
    Результат meta title:<br />
    <b>Льняные шторы купить в интернет-магазине, цены от 2 000 руб. - {$siteName}</b>
    </p>
    <hr />
    <p>
    Пример meta description:<br />
    <b>❤ {=section.seoName}. Цены от {=minMoney} ✔ 100% качественные продукты и материалы. ✔ доставка по Москве и всей России.</b>
    </p>
    <p>
    Результат meta description:<br />
    <b>❤ Льняные шторы. Цены от 2 000 руб. ✔ 100% качественные продукты и материалы. ✔ доставка по Москве и всей России.</b>
    </p>
    <hr />
    <p>
    Доступные переменные:<br />
    <b>{=section.seoName}</b> — сео название раздела<br />
    <b>{=minMoney}</b> — минимальная цена за товар в этом разделе<br />
    <b>{=siteName}</b> — Название сайта<br />
</p>
</div>
</div>
HTML

                    ],
                    'meta_title_template' => [
                        'class' => TextareaField::class
                    ],
                    'meta_description_template' => [
                        'class' => TextareaField::class
                    ],
                    'meta_keywords_template' => [
                        'class' => TextareaField::class
                    ],
                ],
            ],
            'captions' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Captions'),
                'fields' => [
                    'name_one',
                    'name_meny',
                ],
            ],
        ];
    }

}
