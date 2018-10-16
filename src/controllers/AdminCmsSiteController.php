<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\models\CmsSite;
use skeeks\cms\queryfilters\filters\modes\FilterModeEmpty;
use skeeks\cms\queryfilters\filters\modes\FilterModeNotEmpty;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HiddenField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteController extends BackendModelStandartController
{
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
                            'name'     => [
                                'isAllowChangeMode' => false,
                            ],
                            'code'     => [
                                'isAllowChangeMode' => false,
                            ],
                            'active'   => $bool,
                            'def'      => $bool,
                            'image_id' => [
                                'isAllowChangeMode' => true,
                                'modes'             => [
                                    FilterModeNotEmpty::class,
                                    FilterModeEmpty::class,
                                ],
                            ],
                        ],
                    ],
                ],

                "grid" => [
                    'on init'       => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $dataProvider = $e->sender->dataProvider;

                        $query->joinWith('cmsSiteDomains');
                        $query->groupBy(CmsSite::tableName() . ".id");
                        $query->select([
                            CmsSite::tableName() . '.*',
                            'countDomains' => new Expression("count(*)")
                        ]);
                    },

                    'sortAttributes' => [
                        'countDomains' => [
                            'asc' => ['countDomains' => SORT_ASC],
                            'desc' => ['countDomains' => SORT_DESC],
                            'label' => 'Количество доменов',
                            'default' => SORT_ASC
                        ]
                    ],
                    'defaultOrder' => [
                        'def' => SORT_DESC
                    ],
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
                        'countDomains',
                    ],
                    'columns'        => [
                        'active'   => [
                            'class' => BooleanColumn::class,
                        ],
                        'def'      => [
                            'class' => BooleanColumn::class,
                        ],
                        'image_id' => [
                            'class' => ImageColumn2::class,
                        ],
                        'countDomains' => [
                            'value' => function(CmsSite $cmsSite) {
                                return $cmsSite->raw_row['countDomains'];
                            },
                            'attribute' => 'countDomains',
                            'label' => 'Количество доменов'
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

            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
            ],

            "deactivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
            ],
        ]);
    }

    public function updateFields($action)
    {
        $active = [
            'class'       => BoolField::class,
            'formElement' => BoolField::ELEMENT_RADIO_LIST,
            'allowNull'   => false,
            'trueValue'   => 'Y',
            'falseValue'  => 'N',
        ];
        $def = [
            'class'       => BoolField::class,
            'formElement' => BoolField::ELEMENT_RADIO_LIST,
            'allowNull'   => false,
            'trueValue'   => 'Y',
            'falseValue'  => 'N',
        ];

        if ($action->model->def == 'Y') {
            $active = [
                'class'     => HiddenField::class,
                'hint'      => \Yii::t('skeeks/cms', 'Site selected by default always active')
            ];
            $def = [
                'class'     => HiddenField::class,
                'hint'      => \Yii::t('skeeks/cms', 'This site is the site selected by default. If you want to change it, you need to choose a different site, the default site.')
            ];
        }

        $result = [
            'main'    => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'image_id'    => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                        'widgetConfig' => [
                            'accept'   => 'image/*',
                            'multiple' => false,
                        ],
                    ],
                    'name',
                    'code',
                    'active'      => $active,
                    'def'         => $def,
                    'description' => [
                        'class' => TextareaField::class,
                    ],
                    'server_name',
                    'priority',
                ],
            ],

        ];

        if (!$action->model->isNewRecord) {
            $result['domains'] = [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', "Domains"),
                'fields' => [
                    'domains' => [
                        'class' => HtmlBlock::class,
                        'content' => \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
                            'label' => "",
                            'hint' => "",
                            'parentModel' => $action->model,
                            'relation' => [
                                'cms_site_id' => 'id'
                            ],

                            'controllerRoute' => '/cms/admin-cms-site-domain',
                            'gridViewOptions' => [
                                'columns' => [
                                    //['class' => 'yii\grid\SerialColumn'],
                                    'domain',
                                ],
                            ],
                        ])
                    ]
                ],
            ];
        }

        return $result;
    }

}
