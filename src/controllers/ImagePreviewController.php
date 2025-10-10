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
use skeeks\cms\Skeeks;
use skeeks\imagine\Image;
use skeeks\sx\File;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;


/**
 * Class ImagingController
 * @package skeeks\cms\controllers
 */
class ImagePreviewController extends Controller
{
    /**
     * Lists all StorageFile models.
     * @return mixed
     */
    public function actionProcess()
    {
        Skeeks::unlimited();

        $imaging = \Yii::$app->imaging;
        if (!$imaging) {
            //TODO: можно добавить проверку YII ensure...
            throw new NotFoundHttpException("Component Imaging not found");
        }

        $newFileSrc = \Yii::$app->request->getPathInfo();
        //Если расширение файла не совпадает с оригинальным
        $ext = trim((string) \Yii::$app->request->get("ext"));

        if (!$ext) {
            $extension = Imaging::getExtension($newFileSrc);
            $newExtension = $extension;
        } else {
            $extension = $ext;
            $newExtension = Imaging::getExtension($newFileSrc);
        }


        if (!$extension) {
            throw new NotFoundHttpException("Extension not found: ".$newFileSrc);
        }


        if (!$imaging->isAllowExtension($extension)) {
            throw new NotFoundHttpException("Extension '{$extension}' not supported in Imaging component");
        }


        $strposFilter = strpos($newFileSrc, "/".Imaging::THUMBNAIL_PREFIX);
        if (!$strposFilter) {
            throw new NotFoundHttpException("This is not a filter thumbnail: ".$newFileSrc);
        }

        $newFile = File::object($newFileSrc);
        $originalFileSrc = substr($newFileSrc, 0, $strposFilter).".".$extension;

        //TODO: hardcode delete it in the future
        $webRoot = \Yii::getAlias('@webroot');

        $originalFileRoot = $webRoot . DIRECTORY_SEPARATOR . $originalFileSrc;
        $newFileRoot = $webRoot . DIRECTORY_SEPARATOR . $newFileSrc;
        $newFileRootDefault = $webRoot . DIRECTORY_SEPARATOR.str_replace($newFile->getBaseName(), Imaging::DEFAULT_THUMBNAIL_FILENAME . "." . $newExtension, $newFileSrc);



        $originalFile = new File($originalFileRoot);

        if (!$originalFile->isExist()) {
            throw new NotFoundHttpException("The original file is not found: ".\Yii::$app->request->absoluteUrl);
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
                throw new NotFoundHttpException("the control line not found: ".$newFileSrc);
            }

            $string = $imaging->getParamsCheckString($params);
            if ($pramsCheckArray[1] != $string) {
                throw new NotFoundHttpException("Parameters invalid: ".$newFileSrc);
            }
        }



        $filterClass = str_replace("-", "\\", $filterCode);


        if (!class_exists($filterClass)) {
            throw new NotFoundHttpException("Filter class is not created: ".$newFileSrc);
        }

        ArrayHelper::remove($params, "ext");
        /**
         * @var Filter $filter
         */
        $filter = new $filterClass((array)$params);
        if (!is_subclass_of($filter, Filter::className())) {
            throw new NotFoundHttpException("No child filter class: ".$newFileSrc);
        }

        /*if(YII_ENV_DEV) {
            var_dump($newFileRoot);
            var_dump($newFileRootDefault);
            die;
        }*/


        try {
            /*if (YII_ENV_DEV) {
                print_r($filter);die;
            }*/
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
                    /*if(YII_ENV_DEV) {
                        var_dump($newExtension);
                        die;
                    }*/
                    symlink(Imaging::DEFAULT_THUMBNAIL_FILENAME . "." . $newExtension, $newFileRoot);
                }
            }
            
            
            /*$url = \Yii::$app->request->getUrl().($params ?
                    ""//"?" . http_build_query($params) . '&sx-refresh'
                    : '?sx-refresh');*/
            /*echo $url;die;*/

            $showOptions = [];
            //TODO:обратиться к фильтру и получить эти данные из него
            if ($q = ArrayHelper::getValue($params, "q")) {
                $showOptions = [
                    'jpeg_quality' => $q,
                    'webp_quality' => $q,
                ];
            }
            
            Header("HTTP/1.0 200 OK");
            Image::getImagine()->open($newFileRootDefault)->show($newExtension, $showOptions);
            die;
            return '';

            \Yii::$app->response->redirect($url, 302);
            \Yii::$app->end();

        } catch (\Exception $e) {
            throw $e;
            return $e->getMessage();
        }
    }


}
