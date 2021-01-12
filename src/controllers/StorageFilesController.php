<?php
/**
 * StorageFilesController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */


namespace skeeks\cms\controllers;

use skeeks\cms\Exception;
use skeeks\cms\filters\CmsAccessControl;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\modules\admin\controllers\AdminController;
use Yii;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\searchs\StorageFile as StorageFileSearch;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * StorageFileController implements the CRUD actions for StorageFile model.
 */
class StorageFilesController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access-no-guests' => [
                'class' => CmsAccessControl::className(),
                'rules' => [
                    [

                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
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


    public function actionGetFile()
    {
        $newFileSrc = \Yii::$app->request->getPathInfo();
        $extension = Imaging::getExtension($newFileSrc);

        if (!$extension) {
            throw new \yii\base\Exception("Extension not found: ".$newFileSrc);
        }

        print_r($newFileSrc);die;
    }
}
