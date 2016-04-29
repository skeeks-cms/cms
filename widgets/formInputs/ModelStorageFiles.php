<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
namespace skeeks\cms\widgets\formInputs;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\StorageFile;
use yii\base\Exception;
use yii\bootstrap\Alert;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * @property StorageFile[] $files
 * Class StorageImages
 * @package skeeks\cms\widgets\formInputs
 */
class ModelStorageFiles extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];


    public $viewItemTemplate = null;

    /**
     * @param $cmsStorageFile
     * @return string
     */
    public function renderItem($cmsStorageFile)
    {
        return $this->render($this->viewItemTemplate, [
            'model' => $cmsStorageFile
        ]);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            if (!$this->hasModel())
            {
                throw new Exception(\Yii::t('app',"Current widget works only in form with model"));
            }

            if ($this->model->isNewRecord)
            {
                throw new Exception(\Yii::t('app',"Images can be downloaded after you save the form data"));
            }


            if (!$this->model->hasProperty($this->attribute))
            {
                throw new Exception("Relation {$this->attribute} не найдена");
            }

            echo $this->render('model-storage-files', [
                'model'         => $this->model,
                'widget'        => $this,
            ]);

        } catch (\Exception $e)
        {
            echo Alert::widget([
                'options' => [
                      'class' => 'alert-warning',
                ],
                'body' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return null|StorageFile[]
     */
    public function getFiles()
    {
        return $this->model->{$this->attribute};
    }

    public function getJsonString()
    {
        return Json::encode([
            'backendUrl'        => UrlHelper::construct('cms/admin-storage-files/link-to-models')->enableAdmin()->toString(),
            'modelId'           => $this->model->id,
            'modelClassName'    => $this->model->className(),
            'modelRelation'     => $this->attribute,
        ]);
    }
}
