<?php
/**
 * Storage
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\storage;

use Yii;
use yii\base\Component;


use \skeeks\sx\File;
use \skeeks\sx\Dir;
use yii\base\Model;

/**
 * Class Cluster
 * @package skeeks\cms\components\storage
 */
abstract class Cluster extends Model
{
    public $id;
    public $name;
    public $priority = 100;

    public $publicBaseUrl; //   http://c1.s.skeeks.com/uploads/
    public $rootBasePath; //   /var/www/sites/test.ru/frontend/web/uploads/

    /**
     * @var integer the level of sub-directories to store uploaded files. Defaults to 1.
     * If the system has huge number of uploaded files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public $directoryLevel = 3;


    /**
     * @param $file
     * @return string $clusterFileUniqSrc
     */
    abstract public function upload(File $file);


    /**
     * @param $clusterFileUniqSrc
     * @param $file
     * @return mixed
     */
    abstract public function update($clusterFileUniqSrc, $file);

    /**
     * @param $clusterFileUniqSrc
     * @return mixed
     */
    abstract public function delete($clusterFileUniqSrc);


    /**
     * Удаление папки с преьвюшками
     *
     * @param $clusterFileUniqSrc
     * @return mixed
     */
    abstract public function deleteTmpDir($clusterFileUniqSrc);

    /**
     * Путь до папки с временными файлами превью например
     *
     * @param $clusterFileUniqSrc
     * @return string
     */
    public function rootTmpDir($clusterFileUniqSrc)
    {
        $file = new File($this->getRootSrc($clusterFileUniqSrc));
        return $file->getDirName() . "/" . $file->getFileName();
    }


    /**
     * Полный публичный путь до файла.
     *
     * @param $clusterFileSrc
     * @return string
     */
    public function getRootSrc($clusterFileUniqSrc)
    {
        return $this->rootBasePath . DIRECTORY_SEPARATOR . $clusterFileUniqSrc;
    }

    /**
     * Полный публичный путь до файла.
     * Например /uploads/all/f4/df/sadfsd/sdfsdfsd/asdasd.jpg
     *
     * @param $clusterFileSrc
     * @return string
     */
    public function getPublicSrc($clusterFileUniqSrc)
    {
        return $this->publicBaseUrl . "/" . $clusterFileUniqSrc;
    }

    /**
     * @param $clusterFileUniqSrc
     * @return string
     */
    public function getAbsoluteUrl($clusterFileUniqSrc)
    {
        return $this->getPublicSrc($clusterFileUniqSrc);
    }


    /**
     *
     * Дирриктория где будет лежать файл, определяется по имени файла
     *
     * @param $newName
     * @return string
     */
    public function getClusterDir($newName)
    {
        $localDir = "";

        if ($this->directoryLevel > 0) {
            $count = 0;
            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                $count++;
                if (($prefix = substr($newName, $i + $i, 2)) !== false) {
                    if ($count > 1) {
                        $localDir .= DIRECTORY_SEPARATOR;
                    }

                    $localDir .= $prefix;
                }
            }
        }

        return $localDir;
    }

    /**
     *
     * Геренрация названия файла, уникального названия.
     *
     * @param $originalFileName
     * @return string
     */
    protected function _generateClusterFileName(File $originalFileName)
    {
        $originalFileName->getExtension();
        // generate a unique file name
        $newName = md5(microtime() . rand(0, 100));
        return $originalFileName->getExtension() ? $newName . "." . $originalFileName->getExtension() : $newName;
    }


    /**
     * Свободное место на сервере
     * @return float
     */
    public function getFreeSpace()
    {
        return 0;
    }

    /**
     * Всего столько места.
     * @return float
     */
    public function getTotalSpace()
    {
        return 0;
    }

    /**
     * Занятое место
     * @return float
     */
    public function getUsedSpace()
    {
        return (float)($this->getTotalSpace() - $this->getFreeSpace());
    }

    /**
     * Свободно процентов
     * @return float
     */
    public function getFreeSpacePct()
    {
        return ($this->getFreeSpace() * 100) / $this->getTotalSpace();
    }

    /**
     * Занято в процентах
     * @return float
     */
    public function getUsedSpacePct()
    {
        return (float)(100 - $this->getFreeSpacePct());
    }
}
