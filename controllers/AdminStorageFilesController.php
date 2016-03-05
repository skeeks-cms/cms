<?php
/**
 * AdminStorageFilesController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 25.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\Comment;
use skeeks\cms\models\Publication;
use skeeks\cms\models\searchs\Publication as PublicationSearch;
use skeeks\cms\models\StorageFile;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModelBehaviors;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class AdminStorageFilesController
 * @package skeeks\cms\controllers
 */
class AdminStorageFilesController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление файлами хранилища";
        $this->modelClassName          = StorageFile::className();

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
        [
            'delete-tmp-dir' =>
            [
                "class"         => AdminOneModelEditAction::className(),
                "name"          => "Удалить временные файлы",
                "icon"          => "glyphicon glyphicon-folder-open",
                "method"        => "post",
                "request"       => "ajax",
                "callback"      => [$this, 'actionDeleteTmpDir'],
            ],

            'download' =>
            [
                "class"         => AdminOneModelEditAction::className(),
                "name"          => "Скачать",
                "icon"          => "glyphicon glyphicon-circle-arrow-down",
                "method"        => "post",
                "callback"      => [$this, 'actionDownload'],
            ],

            'create' =>
            [
                'visible' => false
            ]
        ]);
    }

    public function actionDownload()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $success = false;

        /**
         * @var StorageFile $file
         */
        $file = $this->model;
        $file->src;


        header('Content-type: ' . $file->mime_type);
        header('Content-Disposition: attachment; filename="' . $file->cluster_file . '"');
        echo file_get_contents($file->cluster()->getAbsoluteUrl($file->cluster_file));
        die;

    }

    public function actionDeleteTmpDir()
    {
        if (\Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;

            /**
             * @var StorageFile $file
             */
            $file = $this->model;
            $file->deleteTmpDir();

            return [
                'message' => "Временные файлы удалены",
                'success' => true,
            ];
        }
    }

}
