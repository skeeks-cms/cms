<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\models\CmsCompany2category;
use skeeks\cms\models\CmsCompanyCategory;
use skeeks\cms\models\CmsCompanyStatus;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealType;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsCompanyCategoryController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Категории компаний");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsCompanyCategory::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [
                "filters" => [
                    'visibleFilters' => [
                        'name',
                    ],
                ],
                'grid'    => [
                    'defaultOrder'   => [
                        'sort' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'name',
                        'sort',
                        'countCompanies',
                    ],
                    'columns'        => [
                        'name' => [
                            'class' => DefaultActionColumn::class,
                            'contentOptions' => [
                                'class' => 'sx-collection-cell__primary',
                            ],
                        ],
                        'sort' => [
                            'format' => 'raw',
                            'value' => static function (CmsCompanyCategory $model) {
                                return Html::tag('span', \Yii::$app->formatter->asInteger($model->sort), [
                                    'class' => 'sx-collection-cell__primary',
                                ]);
                            },
                        ],
                        'countCompanies' => [
                            'attribute'            => 'countCompanies',
                            'format'               => 'raw',
                            'label'                => \Yii::t('skeeks/cms', 'Где используется'),
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $countCompaniesQuery = CmsCompany2category::find()
                                    ->select([new Expression("count(1)")])
                                    ->andWhere([
                                        'cms_company_category_id' => new Expression(CmsCompanyCategory::tableName().".id"),
                                    ]);

                                $query->addSelect([
                                    'countCompanies' => $countCompaniesQuery,
                                ]);

                                $gridView->sortAttributes['countCompanies'] = [
                                    'asc'     => ['countCompanies' => SORT_ASC],
                                    'desc'    => ['countCompanies' => SORT_DESC],
                                    'label'   => \Yii::t('skeeks/cms', 'Где используется'),
                                    'default' => SORT_ASC,
                                ];
                            },
                            'value'                => function (CmsCompanyCategory $model) {
                                return Html::tag(
                                    'span',
                                    Html::tag('strong', \Yii::$app->formatter->asInteger($model->raw_row['countCompanies']))
                                    .Html::tag('small', \Yii::t('skeeks/cms', 'компаний')),
                                    ['class' => 'sx-collection-cell--metric']
                                );
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

        ]);
    }

    public function updateFields($action)
    {


        return [
            'name',

            'sort' => [
                'class' => NumberField::class,
            ],
        ];
    }


}
