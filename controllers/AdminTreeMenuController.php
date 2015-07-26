<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\TreeMenu;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use yii\helpers\ArrayHelper;

/**
 * Class AdminTreeMenuController
 * @package skeeks\cms\controllers
 */
class AdminTreeMenuController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление позициями меню";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = TreeMenu::className();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'index' =>
                [
                    "columns"      => [
                        'name',

                        ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
                        ['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],

                        ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
                    ],
                ],
            ]
        );
    }


}
