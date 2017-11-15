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
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\searchs\StorageFile as StorageFileSearch;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * Class AdminStorageFilesController
 * @package skeeks\cms\controllers
 */
class AdminStorageFilesController extends AdminModelEditorController
{
    public $enableCsrfValidation = false;

    public function init()
    {
        $this->name = "Управление файлами хранилища";
        $this->modelClassName = StorageFile::className();

        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'upload' => ['post'],
                    'remote-upload' => ['post'],
                    'link-to-model' => ['post'],
                    'link-to-models' => ['post'],
                ],
            ],
        ]);
    }


    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'delete-tmp-dir' =>
                    [
                        "class" => AdminOneModelEditAction::className(),
                        "name" => "Удалить временные файлы",
                        "icon" => "glyphicon glyphicon-folder-open",
                        "method" => "post",
                        "request" => "ajax",
                        "callback" => [$this, 'actionDeleteTmpDir'],
                    ],

                'download' =>
                    [
                        "class" => AdminOneModelEditAction::className(),
                        "name" => "Скачать",
                        "icon" => "glyphicon glyphicon-circle-arrow-down",
                        "method" => "post",
                        "callback" => [$this, 'actionDownload'],
                    ],

                'create' =>
                    [
                        'isVisible' => false
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
        echo file_get_contents($file->cluster->getAbsoluteUrl($file->cluster_file));
        die;

    }

    public function actionDeleteTmpDir()
    {
        if (\Yii::$app->request->isAjax) {
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


    public function actionUpload()
    {
        $response =
            [
                'success' => false
            ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->getRequest();


        $dir = \skeeks\sx\Dir::runtimeTmp();

        $uploader = new \skeeks\widget\simpleajaxuploader\backend\FileUpload("imgfile");
        $file = $dir->newFile()->setExtension($uploader->getExtension());

        $originalName = $uploader->getFileName();

        $uploader->newFileName = $file->getBaseName();
        $result = $uploader->handleUpload($dir->getPath() . DIRECTORY_SEPARATOR);

        if (!$result) {
            $response["msg"] = $uploader->getErrorMsg();
            return $result;

        } else {

            $storageFile = Yii::$app->storage->upload($file, array_merge(
                [
                    "name" => "",
                    "original_name" => $originalName
                ]
            ));


            if ($request->get('modelData') && is_array($request->get('modelData'))) {
                $storageFile->setAttributes($request->get('modelData'));
            }

            $storageFile->save(false);

            $response["success"] = true;
            $response["file"] = $storageFile;
            return $response;
        }

        return $response;
    }

    public function actionRemoteUpload()
    {
        $response =
            [
                'success' => false
            ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        $get = Yii::$app->getRequest();

        $request = Yii::$app->getRequest();

        if (\Yii::$app->request->post('link')) {
            $storageFile = Yii::$app->storage->upload(\Yii::$app->request->post('link'), array_merge(
                [
                    "name" => isset($model->name) ? $model->name : "",
                    "original_name" => basename($post['link'])
                ]
            ));

            if ($request->post('modelData') && is_array($request->post('modelData'))) {
                $storageFile->setAttributes($request->post('modelData'));
            }

            $storageFile->save(false);
            $response["success"] = true;
            $response["file"] = $storageFile;
            return $response;
        }

        return $response;
    }


    /**
     * Прикрепить к моделе другой файл
     * @see skeeks\cms\widgets\formInputs\StorageImage
     * @return RequestResponse
     */
    public function actionLinkToModel()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            try {
                if (!\Yii::$app->request->post('file_id') || !\Yii::$app->request->post('modelId') || !\Yii::$app->request->post('modelClassName') || !\Yii::$app->request->post('modelAttribute')) {
                    throw new \yii\base\Exception("Не достаточно входных данных");
                }

                $file = CmsStorageFile::findOne(\Yii::$app->request->post('file_id'));
                if (!$file) {
                    throw new \yii\base\Exception("Возможно файл уже удален или не загрузился");
                }

                if (!is_subclass_of(\Yii::$app->request->post('modelClassName'), ActiveRecord::className())) {
                    throw new \yii\base\Exception("Невозможно привязать файл к этой моделе");
                }

                $className = \Yii::$app->request->post('modelClassName');
                /**
                 * @var $model ActiveRecord
                 */
                $model = $className::findOne(\Yii::$app->request->post('modelId'));
                if (!$model) {
                    throw new \yii\base\Exception("Модель к которой необходимо привязать файл не найдена");
                }

                if (!$model->hasAttribute(\Yii::$app->request->post('modelAttribute'))) {
                    throw new \yii\base\Exception("У модели не найден атрибут привязки файла: " . \Yii::$app->request->post('modelAttribute'));
                }

                //Удаление старого файла
                if ($oldFileId = $model->{\Yii::$app->request->post('modelAttribute')}) {
                    /**
                     * @var $oldFile CmsStorageFile
                     * @var $file CmsStorageFile
                     */
                    $oldFile = CmsStorageFile::findOne($oldFileId);
                    $oldFile->delete();
                }

                $model->{\Yii::$app->request->post('modelAttribute')} = $file->id;
                if (!$model->save(false)) {
                    throw new \yii\base\Exception("Не удалось сохранить модель");
                }

                $file->name = $model->name;
                $file->save(false);

                $rr->success = true;
                $rr->message = "";

            } catch (\Exception $e) {
                $rr->success = false;
                $rr->message = $e->getMessage();
            }

        }

        return $rr;
    }

    /**
     * Прикрепить к моделе другой файл
     * @see skeeks\cms\widgets\formInputs\StorageImage
     * @return RequestResponse
     */
    public function actionLinkToModels()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            try {
                if (!\Yii::$app->request->post('file_id') || !\Yii::$app->request->post('modelId') || !\Yii::$app->request->post('modelClassName') || !\Yii::$app->request->post('modelRelation')) {
                    throw new \yii\base\Exception("Не достаточно входных данных");
                }

                $file = CmsStorageFile::findOne(\Yii::$app->request->post('file_id'));
                if (!$file) {
                    throw new \yii\base\Exception("Возможно файл уже удален или не загрузился");
                }

                if (!is_subclass_of(\Yii::$app->request->post('modelClassName'), ActiveRecord::className())) {
                    throw new \yii\base\Exception("Невозможно привязать файл к этой моделе");
                }

                $className = \Yii::$app->request->post('modelClassName');
                /**
                 * @var $model ActiveRecord
                 */
                $model = $className::findOne(\Yii::$app->request->post('modelId'));
                if (!$model) {
                    throw new \yii\base\Exception("Модель к которой необходимо привязать файл не найдена");
                }

                if (!$model->hasProperty(\Yii::$app->request->post('modelRelation'))) {
                    throw new \yii\base\Exception("У модели не найден атрибут привязки к файлам modelRelation: " . \Yii::$app->request->post('modelRelation'));
                }

                try {
                    $model->link(\Yii::$app->request->post('modelRelation'), $file);

                    if (!$file->name) {
                        $file->name = $model->name;
                        $file->save(false);
                    }

                    $rr->success = true;
                    $rr->message = "";
                } catch (\Exception $e) {
                    $rr->success = false;
                    $rr->message = $e->getMessage();
                }


            } catch (\Exception $e) {
                $rr->success = false;
                $rr->message = $e->getMessage();
            }

        }

        return $rr;
    }

}
