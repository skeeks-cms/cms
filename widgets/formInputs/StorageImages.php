<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
namespace skeeks\cms\widgets\formInputs;

use skeeks\cms\Exception;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\Publication;
use skeeks\cms\modules\admin\Module;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Application;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class StorageImages
 * @package skeeks\cms\widgets\formInputs
 */
class StorageImages extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];

    public $mode = "combo";

    /**
     * Берем поведения модели
     *
     */
    private function _initAndValidate()
    {
        if (!$this->hasModel())
        {
            throw new Exception("Этот файл рассчитан только для форм с моделью");
        }

        Validate::ensure(new HasBehavior(HasFiles::className()), $this->model);
    }

    /**
     * @var Publication the data model that this widget is associated with.
     */
    public $model;
    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            $this->_initAndValidate();

            $groupMainImage = $this->model->getFilesGroups()->getComponent('image');
            $groupImages = $this->model->getFilesGroups()->getComponent('images');


            $moduleUrl = \Yii::$app->controller->module instanceof Application ? '' : \Yii::$app->controller->module->id . '/';

            $uploaderUrl = \skeeks\cms\helpers\UrlHelper::construct($moduleUrl . \Yii::$app->controller->id . '/files', [
                'id' => $this->model->id,
                'group' => "images",
                'mode' => "sx-onlyUpload"
            ])
                ->enableAdmin()
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL, 'true')
            ;




            $uploaderUrlImage = \skeeks\cms\helpers\UrlHelper::construct( $moduleUrl . \Yii::$app->controller->id . '/files', [
                'id' => $this->model->id,
                'group' => "image",
                'mode' => "sx-onlyUpload"
            ])
                ->enableAdmin()
                /*->setSystem([
                    Module::SYSTEM_QUERY_NO_ACTIONS_MODEL => 'true'
                ])*/
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL, 'true')
                ->toString();

            if ($this->model->isNewRecord)
            {
                echo "";
                return;
            }

            echo $this->render('storage-images', [
                'model' => $this->model,
                'widget' => $this,
                'uploaderUrlImage' => $uploaderUrlImage,
                'uploaderUrl' => $uploaderUrl
            ]);

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }

    }

}
