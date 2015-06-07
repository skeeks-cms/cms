<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 07.06.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use Yii;
use yii\web\Response;

/**
 * Class SeoController
 * @package skeeks\cms\controllers
 */
class SeoController extends Controller
{
    public function actionRobots()
    {
        echo \Yii::$app->seo->robotsContent;
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->set('Content-Type', 'text/plain');
        $this->layout = false;
    }
}
