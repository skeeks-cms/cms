<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 13.04.2016
 */

namespace skeeks\cms\controllers;

use skeeks\cms\filters\CmsAccessControl;
use yii\web\Controller;

/**
 * Class ProfileController
 * @package skeeks\cms\controllers
 */
class ProfileController extends Controller
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return
            [
                //Closed all by default
                'access' =>
                    [
                        'class' => CmsAccessControl::className(),
                        'rules' =>
                            [
                                [
                                    'allow' => true,
                                    'roles' => ['@'],
                                    'actions' => ['index'],
                                ]
                            ]
                    ],
            ];
    }

    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        return $this->redirect(\Yii::$app->user->identity->profileUrl);
    }

}
