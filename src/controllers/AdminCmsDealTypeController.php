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
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealType;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsDealTypeController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Типы сделок");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsDealType::class;

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
                "filters" => [
                    'visibleFilters' => [
                        'name',
                    ],
                ],
                'grid'    => [
                    'defaultOrder'   => [
                        'name' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'name',
                    ],
                    'columns'        => [
                        'name' => [
                            'class' => DefaultActionColumn::class,
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

        \Yii::$app->view->registerCss(<<<CSS
.sx-period-wrapper {
    display: none;
}
CSS
        );

        \Yii::$app->view->registerJs(<<<JS
function updatePeriod() {
    var isCHecked = $("#cmsdealtype-is_periodic").is(":checked");
    if (isCHecked) {
        $(".sx-period-wrapper").slideDown();
    } else {
        $(".sx-period-wrapper").slideUp();
    }
}

updatePeriod();
$("#cmsdealtype-is_periodic").on("change", function() {
    updatePeriod();
});
JS
        );
        return [
            'name',

            'is_periodic' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],
            [
                'class' => HtmlBlock::class,
                'content' => '<div class="sx-period-wrapper">'
            ],
            'period' => [
                'class' => SelectField::class,
                'items' => CmsDealType::optionsForPeriod()
            ],
            [
                'class' => HtmlBlock::class,
                'content' => '</div>'
            ],
        ];
    }


}
