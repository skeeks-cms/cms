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
use skeeks\cms\models\CmsCompany;
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

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsCompanyStatusController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Статусы компаний");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsCompanyStatus::class;

        /*$this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;*/

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
                        ],
                        'countCompanies' => [
                            'attribute'            => 'countCompanies',
                            'format'               => 'raw',
                            'label'                => \Yii::t('skeeks/cms', 'Где используется'),
                            'contentOptions'       => [
                                'style' => 'max-width: 120px;',
                            ],
                            'headerOptions'        => [
                                'style' => 'max-width: 120px;',
                            ],
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $countCompaniesQuery = CmsCompany::find()
                                    ->select([new Expression("count(1)")])
                                    ->andWhere([
                                        'cms_company_status_id' => new Expression(CmsCompanyStatus::tableName().".id"),
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
                            'value'                => function (CmsCompanyStatus $model) {
                                return $model->raw_row['countCompanies'];
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
