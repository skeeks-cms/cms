<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsUserUniversalPropertyEnum;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsUserUniversalPropertyEnumController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление значениями свойств пользователя";
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsUserUniversalPropertyEnum::class;

        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'filters' => [
                    'visibleFilters' => [
                        'value',
                        'property_id'
                    ]
                ],
                'grid' => [
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'property_id',
                        'value',
                        'code',
                        'priority',
                    ]
                ]
            ]
        ]);
    }

}
