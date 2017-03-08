<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsLang;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsLangController
 * @package skeeks\cms\controllers
 */
class AdminCmsLangController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                   = "Управление языками";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsLang::className();

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
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "Деактивировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiInActivate'],
                ]
            ]
        );
    }
}
