<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\propertyTypes;

use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * Class PropertyTypeFile
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeStorageFile extends PropertyType
{
    public $code = self::CODE_STORAGE_FILE;
    public $name = "";

    public $is_multiple = false;
    public $accept = 'image/*';


    public $allowExtensions = 'jpg,jpeg,gif,png';
    public $minSize = 1024;
    public $maxSize = 10485760;
    public $maxFiles = 20;

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = "Файлы из хранилища";
        }
    }


    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->field($this, 'is_multiple')->checkbox(\Yii::$app->formatter->booleanFormat);
        echo $activeForm->field($this, 'accept');
        echo $activeForm->field($this, 'allowExtensions');
        echo $activeForm->field($this, 'minSize');
        echo $activeForm->field($this, 'maxSize');
        echo $activeForm->field($this, 'maxFiles');
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'accept' => "Типы файлов разрешенные к загрузке",
            'is_multiple' => \Yii::t('skeeks/cms', 'Multiple choice'),
            'allowExtensions' => "Разрешенные расширения файлов к загрузке",
            'minSize' => "Минимально допустимый размер файла",
            'maxSize' => "Максимально допустимый размер файла",
            'maxFiles' => "Максимальное количество файлов",
        ]);
    }
    public function attributeHints()
    {
        return array_merge(parent::attributeLabels(), [
            'accept' => "image/* - например",
            'allowExtensions' => "Указать через запятую например: jpg,jpeg,gif,png",
            'minSize' => "Значение указываетя в Kb",
            'maxSize' => "Значение указываетя в Kb",
            'maxFiles' => "Значение указывается если выбрана множественная загрузка",
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
                ['is_multiple', 'boolean'],
                ['accept', 'string'],
                ['allowExtensions', 'string'],
                ['minSize', 'integer'],
                ['maxSize', 'integer'],
                ['maxFiles', 'integer'],
            ]);
    }

    /**
     * @return PropertyType
     */
    public function addRules()
    {
        if ($this->isMultiple) {
            $this->property->relatedPropertiesModel->attachBehavior(HasStorageFileMulti::class . $this->property->code, [
                'class' => HasStorageFileMulti::class,
                'fields' => [$this->property->code]
            ]);

            $extensions = [];
            if ($this->allowExtensions) {
                $extensions = explode(",", $this->allowExtensions);
            }

            $this->property->relatedPropertiesModel->addRule($this->property->code, \skeeks\cms\validators\FileValidator::class, [
                'skipOnEmpty' => false,
                'extensions'  => $extensions,
                'maxFiles'    => $this->maxFiles,
                'maxSize'     => $this->maxSize,
                'minSize'     => $this->minSize,
            ]);

        } else {
            $this->property->relatedPropertiesModel->attachBehavior(HasStorageFile::class . $this->property->code, [
                'class' => HasStorageFile::class,
                'fields' => [$this->property->code]
            ]);

            $extensions = [];
            if ($this->allowExtensions) {
                $extensions = explode(",", $this->allowExtensions);
            }

            $this->property->relatedPropertiesModel->addRule($this->property->code, \skeeks\cms\validators\FileValidator::class, [
                'skipOnEmpty' => false,
                'extensions'  => $extensions,
                'maxFiles'    => 1,
                'maxSize'     => $this->maxSize,
                'minSize'     => $this->minSize,
            ]);

        }

        return parent::addRules();
    }

    /**
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->is_multiple;
    }



    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->widget(
            AjaxFileUploadWidget::class,
            [
                'accept' => $this->accept,
                'multiple' => (bool) $this->isMultiple
            ]
        );

        return $field;
    }


    /**
     * @return string
     */
    public function getAsText()
    {
        $value = $this->property->relatedPropertiesModel->getAttribute($this->property->code);

        if ($this->isMultiple) {
            /**
             * @var CmsStorageFile $file
             */
            $files = CmsStorageFile::find()->where(['id' => $value])->all();
            if ($files) {
                return implode(", ", (array) ArrayHelper::map($files, "id", "original_name"));
            }

        } else {
            /**
             * @var CmsStorageFile $file
             */
            $file = CmsStorageFile::findOne($value);
            if ($file) {
                return $file->original_name;
            }
        }

        return "";
    }

    /**
     * @return string
     */
    public function getAsHtml()
    {
        $value = $this->property->relatedPropertiesModel->getAttribute($this->property->code);

        if ($this->isMultiple) {
            /**
             * @var CmsStorageFile $file
             */
            $files = CmsStorageFile::find()->where(['id' => $value])->all();
            if ($files) {
                return implode(", ", (array) ArrayHelper::map($files, "id", function($file) {
                    return Html::a($file->original_name, $file->absoluteSrc, [
                        'data-pjax' => 0,
                        'target' => "_blank"
                    ]);
                }));
            }

        } else {
            /**
             * @var CmsStorageFile $file
             */
            $file = CmsStorageFile::findOne($value);
            if ($file) {
                return Html::a($file->original_name, $file->absoluteSrc, [
                    'data-pjax' => 0,
                    'target' => "_blank"
                ]);
            }
        }

        return "";
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    /*public function beforeSaveValue($value)
    {
        if (is_array($value)) {

        } else {

            if ($value && is_string($value) && ((string)(int)$value != (string)$value)) {
                try {
                    $data = [];


                    $file = \Yii::$app->storage->upload($value, $data);
                    if ($file) {
                        $value = $file->id;
                    } else {
                        $value = null;
                    }

                } catch (\Exception $e) {
                    \Yii::error($e->getMessage());
                    $value = null;
                }
            }
            
        }

        return $value;
    }*/

}