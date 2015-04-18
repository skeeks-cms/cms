<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
namespace skeeks\cms\modules\admin\widgets\formInputs;

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
 * @package skeeks\cms\modules\admin\widgets\formInputs
 */
class StorageImages extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];

    /**
     * @var string
     */
    public $fileGroup = "";

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

            if ($this->model->isNewRecord)
            {
                echo "<br />Файлы можно добавлять после сохранения этой записи";
                return;
            }

            echo $this->render('storage-images', [
                'model' => $this->model,
                'widget' => $this,
                'fileGroup' => $this->fileGroup,
            ]);

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }

    }

}
