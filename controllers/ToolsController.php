<?php
/**
 * Вспомогательные иструменты
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\modules\admin\widgets\UserLastActivityWidget;
use Yii;
use yii\db\ActiveRecord;
use yii\web\Response;


/**
 * Class ToolsController
 * @package skeeks\cms\controllers
 */
class ToolsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => null,
            ],
        ];
    }

    /**
     * Выбор файла
     * @return string
     */
    public function actionSelectFile()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/main.php';
        \Yii::$app->cmsToolbar->enabled = 0;

        $model = null;
        $className = \Yii::$app->request->get('className');
        $pk = \Yii::$app->request->get('pk');

        if ($className && $pk)
        {
            if ($model = $className::findOne($pk))
            {

            }
        }


        return $this->render($this->action->id, [
            'model' => $model
        ]);
    }

    /**
     * Выбор элемента контента
     * @return string
     */
    public function actionSelectCmsElement()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/main.php';
        \Yii::$app->cmsToolbar->enabled = 0;

        return $this->render($this->action->id);
    }

    /**
     * Выбор элемента контента
     * @return string
     */
    public function actionSelectCmsUser()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/main.php';
        \Yii::$app->cmsToolbar->enabled = 0;

        return $this->render($this->action->id);
    }

    /**
     * Данные о текущем пользователе
     * @return RequestResponse
     */
    public function actionGetUser()
    {
        $rr = new RequestResponse();

        $rr->data = [
            'identity'  => \Yii::$app->user->identity,
            'user'      => \Yii::$app->user,
        ];

        return $rr;
    }

    /**
     * Данные о текущем пользователе
     * @return RequestResponse
     */
    public function actionAdminLastActivity()
    {
        $rr = new RequestResponse();

        if (!\Yii::$app->user->isGuest)
        {
            $rr->data = (new UserLastActivityWidget())->getOptions();
        } else
        {
            $rr->data = [
                'isGuest' => true
            ];
        }


        return $rr;
    }

}
