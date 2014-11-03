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
namespace skeeks\cms\models\behaviors\traits;

use skeeks\cms\models\StorageFile;

/**
 * Class HasFiles
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasFiles
{
    /**
     * Файлы привязанные к полю
     * @param $fieldName
     * @return array of src
     */
    public function getFiles($fieldName)
    {
        return (array) $this->{$fieldName};
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
        $this->setAttribute($fieldName, array_unique($files));

        $this->save();
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
        $file->setAttributes($this->getRef()->toArray(), false);
        $file->save();
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

            $this->setAttribute($fieldName, $result);
            $this->save();
        }

        return $this;
    }

}