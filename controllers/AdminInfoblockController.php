<?php
/**
 * AdminInfoblockController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\models\Infoblock;
use skeeks\cms\models\Search;
use skeeks\cms\models\UserGroup;
use skeeks\cms\models\WidgetConfig;
use skeeks\cms\models\WidgetSettings;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\widgets\text\Text;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 *
 * @method Infoblock getCurrentModel()
 *
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminInfoblockController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление инфоблоками";
        $this->_modelShowAttribute      = "name";
        $this->_modelClassName          = Infoblock::className();
        $this->modelValidate            = true;
        $this->enableScenarios          = true;
        parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                "actions" =>
                [
                    'config' =>
                    [
                        "label" => "Настройки",
                        "rules" =>
                        [
                            [
                                "class" => HasModel::className()
                            ]
                        ]
                    ],

                    'rules' =>
                    [
                        "label" => "Правила показа",
                        "method"        => "post",
                        "request"       => "ajax",
                        "rules" =>
                        [
                            [
                                "class" => HasModel::className()
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }


    public function actionRules()
    {
        if (\Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;

            return [
                'message' => 'Еще не реализовно',
                'success' => false,
            ];

        }





        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }



    }

    public function actionConfig()
    {
        $infoblock  = $this->getCurrentModel();
        $widget     = $infoblock->widget();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $widget->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($widget);
        }

        if (\Yii::$app->request->isAjax)
        {
            if ($widget->load(\Yii::$app->request->post()))
            {
                $this->getCurrentModel()->setMultiConfig($widget->attributes);
                $this->getCurrentModel()->save(false);

                \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');

            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }

        } else
        {
            if (\Yii::$app->request->isPost)
            {
                if ($widget->load(\Yii::$app->request->post()))
                {
                    $this->getCurrentModel()->setMultiConfig($widget->attributes);
                    $this->getCurrentModel()->save(false);

                    \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                }
            }
        }



        return $this->render('config', [
            'model' => $widget,
        ]);
    }
}
