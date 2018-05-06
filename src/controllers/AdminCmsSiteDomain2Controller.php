<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsSiteDomain;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteDomain2Controller extends BackendModelStandartController
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
        $updateFields = [
            'domain',
            'cms_site_id',
        ];

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
                'fields' => $updateFields,
            ],
            "update" => [
                'fields' => $updateFields,
            ],
        ]);
    }
}
