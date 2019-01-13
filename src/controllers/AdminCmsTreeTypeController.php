<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeType;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsTreeTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypeController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name = "Настройки разделов";
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsTreeType::className();

        parent::init();
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                "activate-multi" =>
                    [
                        'class' => AdminMultiModelEditAction::className(),
                        "name" => "Активировать",
                        //"icon"              => "fa fa-trash",
                        "eachCallback" => [$this, 'eachMultiActivate'],
                    ],

                "inActivate-multi" =>
                    [
                        'class' => AdminMultiModelEditAction::className(),
                        "name" => "Деактивировать",
                        //"icon"              => "fa fa-trash",
                        "eachCallback" => [$this, 'eachMultiInActivate'],
                    ]
            ]
        );
    }

}
