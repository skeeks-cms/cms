<?php
/**
 * AdminStorageController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 29.01.2015
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendController;
use skeeks\cms\modules\admin\actions\AdminAction;

/**
 * Class AdminStorageFilesController
 * @package skeeks\cms\controllers
 */
class AdminStorageController extends BackendController
{
    public function init()
    {
        $this->name = "Управление серверами";
        
        $this->generateAccessActions = false;

        $this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };
        
        parent::init();
    }

    public function actions()
    {
        return [
            "index" => [
                "class"    => AdminAction::className(),
                "name"     => "Управление серверами",
                "callback" => [$this, 'actionIndex'],
            ],
        ];
    }

    public function actionIndex()
    {
        $clusters = \Yii::$app->storage->getClusters();

        return $this->render($this->action->id);
    }

}
