<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.06.2015
 */
namespace skeeks\cms\helpers;
use skeeks\cms\components\imaging\Filter;

/**
 * Class FileHelper
 * @package skeeks\cms\helpers
 */
class FileHelper extends \yii\helpers\FileHelper
{
    /**
     * Первый существующий файл
     *
     * @param $file1
     * @return null|string
     */
    static public function getFirstExistingFile($file1 /*...*/)
    {
        $files = func_get_args();
        return self::getFirstExistingFileArray($files);
    }

    /**
     * Первый существующий файл
     *
     * @param string[] $files
     * @return string|null
     */
    static public function getFirstExistingFileArray($files = [])
    {
        foreach ($files as $file)
        {
            if (file_exists(\Yii::getAlias($file)))
            {
                return $file;
            }
        }

        return null;
    }
}