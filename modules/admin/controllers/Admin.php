<?php
/**
 * Admin
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\App;
use yii\base\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 *
 * Это самый базовый контроллер админки, завкрывает все дейсвтия авторизацией
 *
 *
 * Class Admin
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class Admin extends Controller
{
    public function behaviors()
    {
        return
        [
            'access' =>
            [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        $this->layout = App::moduleAdmin()->layout;
    }
}