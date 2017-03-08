<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsSite;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsSiteController
 * @package skeeks\cms\controllers
 */
class AdminCmsSiteController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                   = \Yii::t('skeeks/cms', "Site management");
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsSite::className();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                "def-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "По умолчанию",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiDef'],
                    "priority"          => 0,
                ],

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

    /**
     * @param $model
     * @param $action
     * @return bool
     */
    public function eachMultiDef($model, $action)
    {
        try
        {
            $model->def = Cms::BOOL_Y;
            return $model->save(false);
        } catch (\Exception $e)
        {
            return false;
        }
    }

}
