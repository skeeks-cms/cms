<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 20.12.2017
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelController;
use skeeks\cms\models\CmsUser;
use yii\helpers\ArrayHelper;

/**
 * Class UpaPersonalController
 * @package skeeks\cms\controllers
 */
class UpaPersonalController extends BackendModelController
{
    public $defaultAction = 'update';

    public function init()
    {
        $this->name = "Личные настройки";
        $this->modelClassName = \Yii::$app->user->identityClass;
        $this->modelShowAttribute = 'displayName';
        parent::init();
    }

    public function getModel()
    {
        return \Yii::$app->user->identity;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                "update" => ['class' => BackendModelUpdateAction::class],
                "change-password" => [
                    'class' => BackendModelUpdateAction::class,
                    'name' => 'Смена пароля'
                ],
            ]
        );
    }
}