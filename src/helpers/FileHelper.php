<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.06.2015
 */

namespace skeeks\cms\helpers;

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
    public static function getFirstExistingFile($file1 /*...*/)
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
    public static function getFirstExistingFileArray($files = [])
    {
        foreach ($files as $file) {
            if (file_exists(\Yii::getAlias($file))) {
                return $file;
            }
        }

        return null;
    }

    /**
     *
     * Search for files on all connected extensions
     *
     * @param string $fileName
     * @return array
     */
    public static function findExtensionsFiles($fileName = '/config/main.php', $onlyFileExists = true)
    {
        $configs = [];

        $fileNames = [];
        if (is_string($fileName)) {
            $fileNames[] = $fileName;
        } else {
            if (is_array($fileName)) {
                $fileNames = $fileName;
            }
        }

        foreach ((array)\Yii::$app->extensions as $code => $data) {
            if (is_array($data['alias'])) {
                $configTmp = [];

                foreach ($data['alias'] as $code => $path) {
                    foreach ($fileNames as $fileName) {
                        $file = $path . $fileName;
                        if ($onlyFileExists === true) {
                            if (file_exists($file)) {
                                $configs[] = $file;
                            }
                        } else {
                            $configs[] = $file;
                        }
                    }
                }
            }
        }

        return $configs;
    }
}