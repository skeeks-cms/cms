<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsTreeTypePropertyEnum;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTreeTypePropertyEnumController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Managing partition property values');
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsTreeTypePropertyEnum::class;

        parent::init();

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
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'property_id',
                        'value',
                        'code',
                        'priority',
                    ],
                ],
            ],
        ]);
    }

}
