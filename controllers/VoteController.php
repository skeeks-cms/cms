<?php
/**
 * VoteController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\helpers\ModelRef;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Site controller
 */
class VoteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['trigger'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'trigger' => ['post'],
                ],
            ],
        ];
    }

    public function actionTrigger()
    {
        $response =
        [
            'success' => false
        ];
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->getRequest();

        $ref = ModelRef::createFromData($request->post());
        if ($ref)
        {
            if ($model = $ref->findModel())
            {
                /**
                 * @var \common\models\Game $model
                 */
                //Голос текущего польозвателя
                $model->vote($request->post("value"));

                $response["success"] = true;
                $response["model"] = $ref->findModel();
            }
        }

        return $response;
    }
}
