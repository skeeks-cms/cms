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
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HiddenField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteDomainController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Managing domains");
        $this->modelShowAttribute = "domain";
        $this->modelClassName = CmsSiteDomain::class;

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
            'index'  => [
                "backendShowings" => false,
                "filters"         => [
                    'visibleFilters' => [
                        //'id',
                        'domain',
                        //'cms_site_id',
                    ],
                ],
                'grid'            => [
                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
                    },


                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        //'id',
                        'domain',
                        //'cms_site_id',
                        'is_main',
                        'is_https',
                    ],
                    'columns'        => [
                        'domain'   => [
                            'class' => DefaultActionColumn::class,
                        ],
                        'is_main'  => [
                            'class'      => BooleanColumn::class,
                            'trueValue'  => 1,
                            'falseValue' => 0,
                        ],
                        'is_https' => [
                            'class'      => BooleanColumn::class,
                            'trueValue'  => 1,
                            'falseValue' => 0,
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
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        if ($code = \Yii::$app->request->get('cms_site_id')) {
            $model->cms_site_id = $code;
            $field = [
                'class' => HiddenField::class,
                'label' => false,
            ];
        } else {
            $field = [
                'class' => SelectField::class,
                'items' => function () {
                    return ArrayHelper::map(CmsSite::find()->all(), 'id', 'asText');
                },
            ];
        }
        $updateFields = [
            'domain',
            'is_main'     => [
                'class'       => BoolField::class,
                'allowNull'   => false,
                'formElement' => BoolField::ELEMENT_CHECKBOX,
            ],
            'is_https'    => [
                'class'       => BoolField::class,
                'allowNull'   => false,
                'formElement' => BoolField::ELEMENT_CHECKBOX,
            ],
            [
                'class'   => HtmlBlock::class,
                'content' => "<div style='display: none;'>",
            ],
            'cms_site_id' => $field,
            [
                'class'   => HtmlBlock::class,
                'content' => "</div>",
            ],
        ];

        return $updateFields;
    }
}
