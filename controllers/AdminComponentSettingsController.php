<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
namespace skeeks\cms\controllers;
use skeeks\cms\base\Component;
use skeeks\cms\components\Cms;
use skeeks\cms\modules\admin\controllers\AdminController;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Class AdminComponentSettingsController
 * @package skeeks\cms\controllers
 */
class AdminComponentSettingsController extends AdminController
{
    public function init()
    {
        $this->_label                   = "Управление настройками компонентов";
        parent::init();
    }

    public function actionIndex()
    {
        $componentClassName         = \Yii::$app->request->get('componentClassName');
        $attributes                 = \Yii::$app->request->get('attributes');

        $component = new $componentClassName($attributes);
        if ($component && $component instanceof Component)
        {
            if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
            {
                $component->load(\Yii::$app->request->post());
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($component);
            }

            if (\Yii::$app->request->isAjax)
            {
                if ($component->load(\Yii::$app->request->post()))
                {
                    if ($component->saveDefaultSettings())
                    {
                        \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                    } else
                    {
                        \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                    }

                } else
                {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }

            }
        }

        return $this->render('index', [
            'component'         => $component
        ]);
    }

}
