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

use skeeks\cms\components\Imaging;
use skeeks\cms\Exception;
use skeeks\cms\helpers\FileHelper;
use skeeks\sx\File;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * StorageFileController implements the CRUD actions for StorageFile model.
 */
class StorageFileController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionGetFile()
    {
        $newFileSrc = \Yii::$app->request->getPathInfo();
        $extension = Imaging::getExtension($newFileSrc);

        if (!$extension) {
            throw new \yii\base\Exception("Extension not found: ".$newFileSrc);
        }

        $strposFilter = strpos($newFileSrc, "/".Imaging::STORAGE_FILE_PREFIX);
        if (!$strposFilter) {
            throw new NotFoundHttpException("This is not a filter storage file: ".$newFileSrc);
        }

        $newFile = File::object($newFileSrc);
        $originalFileSrc = substr($newFileSrc, 0, $strposFilter).".".$newFile->getExtension();

        //TODO: hardcode delete it in the future
        //TODO: hardcode delete it in the future
        $webRoot = \Yii::getAlias('@webroot');

        $originalFileRoot = $webRoot . DIRECTORY_SEPARATOR . $originalFileSrc;
        $newFileRoot = $webRoot . DIRECTORY_SEPARATOR . $newFileSrc;

        $originalFile = new File($originalFileRoot);

        if (!$originalFile->isExist()) {
            throw new NotFoundHttpException("The original file is not found: ".\Yii::$app->request->absoluteUrl);
        }

        FileHelper::createDirectory($newFile->getDirName());

        //print_r($originalFile->getBaseName());die;
        symlink("../../" . $originalFile->getBaseName(), $newFileRoot);

        $url = \Yii::$app->request->getUrl().(\Yii::$app->request->get() ?
                ""//"?" . http_build_query($params) . '&sx-refresh'
                : '');

        return \Yii::$app->response->redirect($url, 302);
        
    }
}
