<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
use skeeks\yii2\form\fields\HiddenField;
use skeeks\yii2\form\fields\SelectField;
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
                        'id',
                        'domain',
                        'cms_site_id',
                    ],
                ],
                'grid'    => [
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'domain',
                        'cms_site_id',
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
        /**
         * @var $model CmsSiteDomain
         */
        $model = $action->model;

        if ($code = \Yii::$app->request->get('cms_site_id'))
        {
            $model->cms_site_id = $code;
            $field = [
                'class' => HiddenField::class,
                'label' => false
            ];
        } else {
            $field = [
                'class' => SelectField::class,
                'items' => function() {
                    return ArrayHelper::map(CmsSite::find()->all(), 'id', 'asText');
                }
            ];
        }
        $updateFields = [
            'domain',
            'cms_site_id' => $field
        ];

        return $updateFields;
    }
}
