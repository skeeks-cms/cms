<?php
/**
 * InfoController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\cms\modules\admin\models\forms\SshConsoleForm;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\sx\Dir;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use Yii;
use yii\web\Response;

/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class SshController extends AdminController
{
    public function init()
    {
        $this->name = \Yii::t('app',"{ssh} console",['ssh' => 'Ssh']);

        parent::init();
    }

    public function actions()
    {
        return
        [
            "index" =>
            [
                "class"        => AdminAction::className(),
                "name"         => \Yii::t('app',"Console"),
                "callback"     => [$this, 'actionIndex'],
            ],

        ];
    }

    public function actionConsole()
    {
        return $this->render($this->action->id, [
            'action' => $this->action
        ]);
    }

    public function actionIndex()
    {
        $model = new SshConsoleForm();

        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        $result     = "";
        $command    = "";

        if ($model->load(\Yii::$app->request->post()) && $model->validate())
        {
            $command    = $model->command;
            ob_start();
            system("cd " . ROOT_DIR . "; {$command};");
            $result = ob_get_clean();
            $result = '1';
        }*/

        return $this->render($this->action->id, [
            /*'model'     => $model,
            'result'    => $result*/
        ]);
    }

}