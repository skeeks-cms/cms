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
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class MainStorageFile
 * @package skeeks\cms\widgets\formInputs
 */
class MainStorageFile extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];

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

            $group = $this->model->getFilesGroups()->getComponent('image');

            if ($this->model->isNewRecord)
            {
                echo "";
                return;
            }

            echo $this->render('main-storage-file', [
                'model' => $this->model,
                'widget' => $this
            ]);

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }

    }

}
