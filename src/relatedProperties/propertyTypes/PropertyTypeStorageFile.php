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
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
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
    /**
     * @var int Минимальный размер файла в байтах (так хранится в настройках характеристики).
     *          В форме настроек редактируется через minSizeKb.
     */
    public $minSize = 1024;
    /**
     * @var int Максимальный размер файла в байтах (так хранится в настройках характеристики).
     *          В форме настроек редактируется через maxSizeMb.
     */
    public $maxSize = 10485760;
    public $maxFiles = 20;

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = "Файл";
        }
    }


    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function renderConfigFormFields(ActiveForm $activeForm)
    {
        $result = $activeForm->field($this, 'is_multiple')->checkbox(\Yii::$app->formatter->booleanFormat);
        $result .= $activeForm->field($this, 'accept');
        $result .= $activeForm->field($this, 'allowExtensions');
        $result .= $activeForm->field($this, 'minSizeKb')->input('number', ['step' => 'any', 'min' => 0]);
        $result .= $activeForm->field($this, 'maxSizeMb')->input('number', ['step' => 'any', 'min' => 0.01]);
        $result .= $activeForm->field($this, 'maxFiles');

        return $result;
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'accept' => "Типы файлов разрешенные к загрузке",
            'is_multiple' => \Yii::t('skeeks/cms', 'Multiple choice'),
            'allowExtensions' => "Разрешенные расширения файлов к загрузке",
            'minSize' => "Минимально допустимый размер файла, байт",
            'maxSize' => "Максимально допустимый размер файла, байт",
            'minSizeKb' => "Минимально допустимый размер файла, КБ",
            'maxSizeMb' => "Максимально допустимый размер файла, МБ",
            'maxFiles' => "Максимальное количество файлов",
        ]);
    }
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'accept' => "image/* - например",
            'allowExtensions' => "Указать через запятую например: jpg,jpeg,gif,png",
            'minSizeKb' => "В килобайтах. Например: 1 — файлы меньше 1 КБ не принимаются.",
            'maxSizeMb' => "В мегабайтах. Например: 100 — файлы больше 100 МБ не принимаются. Можно дробное число: 0.5.",
            'maxFiles' => "Значение указывается если выбрана множественная загрузка",
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
                ['is_multiple', 'boolean'],
                ['accept', 'string'],
                ['allowExtensions', 'string'],
                ['minSize', 'integer', 'min' => 0],
                ['maxSize', 'integer', 'min' => 1],
                ['minSizeKb', 'number', 'min' => 0],
                ['maxSizeMb', 'number', 'min' => 0.01],
                ['maxFiles', 'integer'],
            ]);
    }

    /**
     * @return PropertyType
     */
    public function addRules(RelatedPropertiesModel $relatedPropertiesModel)
    {
        if ($this->isMultiple) {
            $relatedPropertiesModel->attachBehavior(HasStorageFileMulti::class . $this->property->code, [
                'class' => HasStorageFileMulti::class,
                'fields' => [$this->property->code]
            ]);

            $extensions = [];
            if ($this->allowExtensions) {
                $extensions = explode(",", $this->allowExtensions);
            }

            $relatedPropertiesModel->addRule($this->property->code, \skeeks\cms\validators\FileValidator::class, [
                'skipOnEmpty' => false,
                'extensions'  => $extensions,
                'checkExtensionByMimeType'    => false,
                'maxFiles'    => $this->maxFiles,
                'maxSize'     => $this->maxSize,
                'minSize'     => $this->minSize,
            ]);

        } else {
            $relatedPropertiesModel->attachBehavior(HasStorageFile::class . $this->property->code, [
                'class' => HasStorageFile::class,
                'fields' => [$this->property->code]
            ]);

            $extensions = [];
            if ($this->allowExtensions) {
                $extensions = explode(",", $this->allowExtensions);
            }

            $relatedPropertiesModel->addRule($this->property->code, \skeeks\cms\validators\FileValidator::class, [
                'skipOnEmpty' => false,
                'extensions'  => $extensions,
                'checkExtensionByMimeType'    => false,
                'maxFiles'    => 1,
                'maxSize'     => $this->maxSize,
                'minSize'     => $this->minSize,
            ]);

        }

        return parent::addRules($relatedPropertiesModel);
    }

    /**
     * Максимальный размер в МБ для формы настроек. Хранится по-прежнему maxSize в байтах,
     * поэтому уже сохранённые настройки характеристик читаются без изменений.
     * @return float|null
     */
    public function getMaxSizeMb()
    {
        return $this->maxSize === null || $this->maxSize === '' ? null : round((int)$this->maxSize / 1048576, 2);
    }

    /**
     * @param float|string $value
     */
    public function setMaxSizeMb($value)
    {
        $this->maxSize = ($value === null || $value === '') ? $this->maxSize : (int)round((float)$value * 1048576);
    }

    /**
     * Минимальный размер в КБ для формы настроек (хранится minSize в байтах).
     * @return float|null
     */
    public function getMinSizeKb()
    {
        return $this->minSize === null || $this->minSize === '' ? null : round((int)$this->minSize / 1024, 2);
    }

    /**
     * @param float|string $value
     */
    public function setMinSizeKb($value)
    {
        $this->minSize = ($value === null || $value === '') ? 0 : (int)round((float)$value * 1024);
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
    public function renderForActiveForm(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $field = parent::renderForActiveForm($relatedPropertiesModel);

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
    public function getAsText(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $value = $relatedPropertiesModel->getAttribute($this->property->code);

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
    public function getAsHtml(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $value = $relatedPropertiesModel->getAttribute($this->property->code);

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