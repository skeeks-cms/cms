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
        $newFileSrc                     = \Yii::$app->request->getPathInfo();
        $newFile                        = File::object($newFileSrc);

        $originalFileSrc                = str_replace(DIRECTORY_SEPARATOR . $newFile->getFileName(), '', $newFileSrc);

        $webRoot = \Yii::getAlias('@webroot');

        $originalFileRoot   = $webRoot . DIRECTORY_SEPARATOR . $originalFileSrc;
        $newFileRoot        = $webRoot . DIRECTORY_SEPARATOR . $newFileSrc;

        $originalFile       = new File($originalFileRoot);

        if (!$originalFile->isExist())
        {
            throw new \ErrorException("Не дочерний класс фильтра");
        }

        $params                         = String::compressBase64DecodeUrl($newFile->getFileName());
        list($filterCode, $options)     = $params;

        $filterDescription = \Yii::$app->registeredModels->getComponent((string) $filterCode);

        if (!$filterDescription) {
            throw new \ErrorException("Не дочерний класс фильтра");
        }

        $filterClass = $filterDescription->modelClass;
        if (!class_exists($filterClass))
        {
            throw new \ErrorException("Не дочерний класс фильтра");
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
            $filter->setOriginalRootFilePath($originalFileRoot)->setNewRootFilePath($newFileRoot)->save();
            return \Yii::$app->response->redirect(\Yii::$app->request->getUrl(), 301);
        } catch(\Exception $e)
        {
            return $e->getMessage();
        }
    }


}
