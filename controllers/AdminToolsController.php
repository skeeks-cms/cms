<?php
/**
 * AdminTreeController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\UserLastActivityWidget;
use skeeks\cms\rbac\CmsManager;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminToolsController extends AdminController
{
    /**
     * Проверка доступа к админке
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),
        [
            //Проверка доступа к админ панели
            'adminViewEditAccess' =>
            [
                'class' => AdminAccessControl::className(),
                'only' => ['view-file-edit'],
                'rules' =>
                [
                    [
                        'allow' => true,
                        'roles' =>
                        [
                            CmsManager::PERMISSION_EDIT_VIEW_FILES
                        ],
                    ],
                ]
            ],
        ]);
    }

    public function init()
    {
        $this->name                   = "Управление шаблоном";
        parent::init();
    }

    /**
     * The name of the privilege of access to this controller
     * @return string
     */
    public function getPermissionName()
    {
        return '';
    }

    public function actionViewFileEdit()
    {
        $rootViewFile = \Yii::$app->request->get('root-file');

        $model = new ViewFileEditModel([
            'rootViewFile' => $rootViewFile
        ]);

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()))
            {
                if (!$model->saveFile())
                {
                    $rr->success = false;
                    $rr->message = "Не удалось сохранить файл.";
                }

                $rr->message = "Сохранено";
                $rr->success = true;
            }

            return $rr;
        }

        return $this->render($this->action->id, [
            'model' => $model
        ]);
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
