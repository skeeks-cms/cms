<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.03.2015
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\cms\modules\admin\models\forms\EmailConsoleForm;
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
class EmailController extends AdminController
{
    public function init()
    {
        $this->name = \Yii::t('app',"Testing send email messages from site");

        parent::init();
    }

    public function actions()
    {
        return
        [
            "index" =>
            [
                "class"        => AdminAction::className(),
                "name"         => \Yii::t('app',"Testing sending email"),
                "callback"     => [$this, 'actionIndex'],
            ],
        ];
    }

    public function actionIndex()
    {
        $model = new EmailConsoleForm();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        $result     = "";

        if ($model->load(\Yii::$app->request->post()) && $model->execute())
        {
            $result         = \Yii::t('app',"Submitted");
        } else
        {
            if (\Yii::$app->request->post())
            {
                $result         = \Yii::t('app',"Not sent");
            }
        }

        return $this->render('index', [
            'model'     => $model,
            'result'    => $result,
        ]);
    }




}