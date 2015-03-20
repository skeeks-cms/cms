<?php
/**
 * ErrorController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use Yii;
use yii\web\Response;


/**
 * Class ToolbarController
 * @package skeeks\cms\controllers
 */
class ToolbarController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }

    public function actionTriggerEditMode()
    {
        if (\Yii::$app->request->isPost && \Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;

            \Yii::$app->cmsToolbar->triggerEditMode();

            return [
                'message' => '',
                'success' => true,
            ];
        }
    }

}
