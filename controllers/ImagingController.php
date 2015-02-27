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
use skeeks\cms\models\helpers\ModelRef;
use skeeks\sx\Dir;
use skeeks\sx\File;
use skeeks\sx\models\Ref;
use skeeks\sx\String;
use Yii;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\searchs\StorageFile as StorageFileSearch;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

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
        $imaging                        = \Yii::$app->imaging;
        if (!$imaging)
        {
            //TODO: можно добавить проверку YII ensure...
            throw new \yii\base\Exception("Component Imaging not found");
        }

        $newFileSrc                     = \Yii::$app->request->getPathInfo();
        $extension                      = Imaging::getExtension($newFileSrc);

        if (!$extension)
        {
            throw new \yii\base\Exception("Extension not found");
        }


        if (!$imaging->isAllowExtension($extension))
        {
            throw new \yii\base\Exception("Extension '{$extension}' not supported in Imaging component");
        }


        $newFile                        = File::object($newFileSrc);
        $strposFilter                         = strpos($newFileSrc, DIRECTORY_SEPARATOR . Imaging::THUMBNAIL_PREFIX);
        if (!$strposFilter)
        {
            throw new \ErrorException("Это не thumbnail фильтр");
        }

        $originalFileSrc                = substr($newFileSrc, 0, $strposFilter) . "." . $newFile->getExtension();

        $webRoot = \Yii::getAlias('@webroot');

        $originalFileRoot           = $webRoot . DIRECTORY_SEPARATOR . $originalFileSrc;
        $newFileRoot                = $webRoot . DIRECTORY_SEPARATOR . $newFileSrc;
        $newFileRootDefault         = $webRoot . DIRECTORY_SEPARATOR . str_replace($newFile->getBaseName(), Imaging::DEFAULT_THUMBNAIL_FILENAME . "." . $extension, $newFileSrc);

        $originalFile       = new File($originalFileRoot);

        if (!$originalFile->isExist())
        {
            throw new \ErrorException("Оригинальный файл не найден");
        }

        //Проверено наличие оригинального файла, есть пути к оригиналу, и результирующему файлу.
        //Отслось собрать фильтр, и проверить наличие параметров. А так же проверить разрешены ли эти параметры, для этого в строке есть захэшированный ключь

        $filterSting = substr($newFileSrc, ($strposFilter + strlen(DIRECTORY_SEPARATOR . Imaging::THUMBNAIL_PREFIX) ), strlen($newFileSrc));
        $filterCode = explode("/", $filterSting);
        $filterCode = $filterCode[0]; //Код фильтра

        //Если указаны парамтры, то ноужно проверить контрольную строчку, и если они не соответствуют ей, то ничего делать не будем
        if ($params = \Yii::$app->request->get())
        {
            $pramsCheckArray = explode(DIRECTORY_SEPARATOR, $filterSting);
            if (count($pramsCheckArray) < 3)
            {
                throw new \yii\base\Exception("Не найдена контрольная строка");
            }

            $string = $imaging->getParamsCheckString($params);
            if ($pramsCheckArray[1] != $string)
            {
                throw new \yii\base\Exception("Параметры невалидны");
            }
        }

        $filterDescription = \Yii::$app->registeredModels->getComponent((string) $filterCode);

        if (!$filterDescription) {
            $filterClass = str_replace("-", "\\", $filterCode);
        } else
        {
            $filterClass = $filterDescription->modelClass;
        }


        if (!class_exists($filterClass))
        {
            throw new \ErrorException("Класс фильтра не создан");
        }

        /**
         * @var Filter $filter
         */
        $filter = new $filterClass((array) $options);
        if (!is_subclass_of($filter, Filter::className()))
        {
            throw new \ErrorException("Не дочерний класс фильтра");
        }

        try
        {
            //Проверяем а создан ли уже файл, и если да то просто делаем на него ссылку.
                $filter
                    ->setOriginalRootFilePath($originalFileRoot)
                    ->setNewRootFilePath($newFileRootDefault)
                    ->save()
                ;

            if (PHP_OS === 'Windows')
            {
                if ($newFileRoot != $newFileRootDefault)
                {
                    //Не тестировалось
                    copy($newFileRootDefault, $newFileRoot);
                }
            }
            else
            {
                if ($newFileRoot != $newFileRootDefault)
                {
                    symlink($newFileRootDefault, $newFileRoot);
                }
            }

            return \Yii::$app->response->redirect(\Yii::$app->request->getUrl() . ($params ? $prams . '&sx-refresh' : '?sx-refresh'), 301);

        } catch(\Exception $e)
        {
            return $e->getMessage();
        }
    }


}
