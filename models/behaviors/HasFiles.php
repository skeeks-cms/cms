<?php
/**
 * HasFiles
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\components\storage\Exception;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\Vote;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 *
 *
 *
 *             [
                "class"  => behaviors\HasFiles::className(),
                "fields" =>
                [
                    "images" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 2000,
                        behaviors\HasFiles::MAX_SIZE            => 200,
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 1,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "files" => [],
                    "image_cover" => [],
                    "image" => []
                ]
            ],
 *
 *
 * Class HasComments
 * @package common\models\behaviors
 */
class HasFiles extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\StorageFile'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные файлы";

    const MAX_SIZE_TOTAL        = "maxSizeTotal";
    const MAX_SIZE              = "maxSize";
    const ALLOWED_EXTENSIONS    = "allowedExtensions";
    const MAX_COUNT_FILES       = "maxCountFiles";
    const ACCEPT_MIME_TYPE      = "acceptMimeType";

    public $fields = [];



    public function attach($owner)
    {
        $owner->attachBehavior("implode_files", [
            "class"  => Implode::className(),
            "fields" =>  [
                "image_cover", "image", "images", "files"
            ]
        ]);

        parent::attach($owner);
    }



    /**
     * @param $optionName
     * @param $fieldName
     * @param null $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function getOptionForField($optionName, $fieldName, $defaultValue = null)
    {
        if (!$this->hasField($fieldName))
        {
            throw new Exception("Для данного бехевера не описано это поле");
        }

        $config = $this->getFieldConfig($fieldName);
        return \yii\helpers\ArrayHelper::getValue($config, $optionName, $defaultValue);
    }





    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Применяется ли к полю
     * @param string $fieldName
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Берем настройки для поля
     * @param string $fieldName
     * @return array
     */
    public function getFieldConfig($fieldName)
    {
        return $this->hasField($fieldName) ? (array) $this->fields[$fieldName] : [];
    }










    /**
     * Файлы привязанные к полю
     * @param $fieldName
     * @return array of src
     */
    public function getFiles($fieldName)
    {
        return (array) $this->owner->{$fieldName};
    }

    /**
     * @param StorageFile $file
     * @param $fieldName
     * @return $this
     */
    protected function _appendFile(StorageFile $file, $fieldName)
    {
        $files              = $this->getFiles($fieldName);
        $files[]            = $file->src;
        $this->owner->setAttribute($fieldName, array_unique($files));

        $this->owner->save(false);
        return $this;
    }

    /**
     *
     * Вставка файла в нужное поле
     *
     * Привязывается файл к этой сущьности
     * Вставляется src в поле сущьности
     *
     * @param StorageFile $file
     * @param $fieldName
     * @return $this
     */
    public function appendFile(StorageFile $file, $fieldName)
    {
        //Вяжем файл к этой сущьности
        $file->setAttributes($this->owner->getRef()->toArray(), false);
        $file->save(false);
        $this->_appendFile($file, $fieldName);

        return $this;
    }

    /**
     * @param $fieldName
     * @param $src
     * @return $this
     */
    public function detachFile($fieldName, $src)
    {
        $files  = $this->getFiles($fieldName);

        $result = [];
        if ($files)
        {
            foreach ($files as $fileSrc)
            {
                if ($fileSrc != $src)
                {
                    $result[] = $fileSrc;
                }
            }

            $this->owner->setAttribute($fieldName, $result);
            $this->owner->save(false);
        }

        return $this;
    }

}