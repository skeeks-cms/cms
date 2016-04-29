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
class StorageFilesController extends AdminController
{
    public $enableCsrfValidation = false;

    public function getPermissionName()
    {
        return '';
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


}
