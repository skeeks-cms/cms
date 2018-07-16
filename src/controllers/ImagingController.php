<?php
/**
 * ImagingController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\components\Imaging;
use skeeks\cms\components\imaging\Filter;
use skeeks\cms\Exception;
use skeeks\sx\File;
use yii\web\Controller;
use yii\web\NotFoundHttpException;


/**
 * Class ImagingController
 * @package skeeks\cms\controllers
 */
class ImagingController extends Controller
{
    /**
     * Lists all StorageFile models.
     * @return mixed
     */
    public function actionProcess()
    {
        ini_set("memory_limit", "512M");

        $imaging = \Yii::$app->imaging;
        if (!$imaging) {
            //TODO: можно добавить проверку YII ensure...
            throw new \yii\base\Exception("Component Imaging not found");
        }

        $newFileSrc = \Yii::$app->request->getPathInfo();
        $extension = Imaging::getExtension($newFileSrc);

        if (!$extension) {
            throw new \yii\base\Exception("Extension not found: ".$newFileSrc);
        }


        if (!$imaging->isAllowExtension($extension)) {
            throw new \yii\base\Exception("Extension '{$extension}' not supported in Imaging component");
        }


        $newFile = File::object($newFileSrc);
        $strposFilter = strpos($newFileSrc, "/".Imaging::THUMBNAIL_PREFIX);
        if (!$strposFilter) {
            throw new NotFoundHttpException("This is not a filter thumbnail: ".$newFileSrc);
        }

        $originalFileSrc = substr($newFileSrc, 0, $strposFilter).".".$newFile->getExtension();

        //TODO: hardcode delete it in the future
        $webRoot = \Yii::getAlias('@webroot');

        $originalFileRoot = $webRoot.DIRECTORY_SEPARATOR.$originalFileSrc;
        $newFileRoot = $webRoot.DIRECTORY_SEPARATOR.$newFileSrc;
        $newFileRootDefault = $webRoot.DIRECTORY_SEPARATOR.str_replace($newFile->getBaseName(),
                Imaging::DEFAULT_THUMBNAIL_FILENAME.".".$extension, $newFileSrc);

        $originalFile = new File($originalFileRoot);

        if (!$originalFile->isExist()) {
            throw new NotFoundHttpException("The original file is not found: ".$newFileSrc);
        }

        //Проверено наличие оригинального файла, есть пути к оригиналу, и результирующему файлу.
        //Отслось собрать фильтр, и проверить наличие параметров. А так же проверить разрешены ли эти параметры, для этого в строке есть захэшированный ключь

        $filterSting = substr($newFileSrc, ($strposFilter + strlen(DIRECTORY_SEPARATOR.Imaging::THUMBNAIL_PREFIX)),
            strlen($newFileSrc));
        $filterCode = explode("/", $filterSting);
        $filterCode = $filterCode[0]; //Код фильтра

        //Если указаны парамтры, то ноужно проверить контрольную строчку, и если они не соответствуют ей, то ничего делать не будем
        if ($params = \Yii::$app->request->get()) {
            $pramsCheckArray = explode(DIRECTORY_SEPARATOR, $filterSting);
            if (count($pramsCheckArray) < 3) {
                throw new \yii\base\Exception("the control line not found: ".$newFileSrc);
            }

            $string = $imaging->getParamsCheckString($params);
            if ($pramsCheckArray[1] != $string) {
                throw new \yii\base\Exception("Parameters invalid: ".$newFileSrc);
            }
        }

        $filterClass = str_replace("-", "\\", $filterCode);


        if (!class_exists($filterClass)) {
            throw new \ErrorException("Filter class is not created: ".$newFileSrc);
        }

        /**
         * @var Filter $filter
         */
        $filter = new $filterClass((array)$params);
        if (!is_subclass_of($filter, Filter::className())) {
            throw new NotFoundHttpException("No child filter class: ".$newFileSrc);
        }

        try {
            //Проверяем а создан ли уже файл, и если да то просто делаем на него ссылку.
            $filter
                ->setOriginalRootFilePath($originalFileRoot)
                ->setNewRootFilePath($newFileRootDefault)
                ->save();

            if (PHP_OS === 'Windows') {
                if ($newFileRoot != $newFileRootDefault) {
                    //Не тестировалось
                    copy($newFileRootDefault, $newFileRoot);
                }
            } else {
                if ($newFileRoot != $newFileRootDefault) {
                    symlink($newFileRootDefault, $newFileRoot);
                }
            }

            $url = \Yii::$app->request->getUrl().($params ?
                    ""//"?" . http_build_query($params) . '&sx-refresh'
                    : '?sx-refresh');

            /*Header("HTTP/1.0 200 OK");
            Image::getImagine()->open($newFileRootDefault)->show('png');
            return '';*/

            return \Yii::$app->response->redirect($url, 302);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


}
