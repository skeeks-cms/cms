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

use \skeeks\sx\File;
use \skeeks\sx\Dir;

/**
 * Class Storage
 * @package common\components\Storage
 */
class ClusterLocal extends Cluster
{
    public function init()
    {
        if (!$this->name)
        {
            $this->name = \Yii::t('skeeks/cms',"Local storage");
        }
        if (!$this->publicBaseUrl)
        {
            $this->publicBaseUrl = \Yii::getAlias("@web/uploads/all");
        }
        if (!$this->rootBasePath)
        {
            $this->rootBasePath = \Yii::getAlias("@frontend/web/uploads/all");
        }
        parent::init();
    }
    /**
     * @var bool
     */
    public $publicBaseUrlIsAbsolute = false;

    /**
     * Добавление файла в кластер
     *
     * @param File $tmpFile
     * @return string
     * @throws Exception
     */
    public function upload(File $tmpFile)
    {
        $clusterFileName     =  $this->_generateClusterFileName($tmpFile);

        $dir                =  $this->rootBasePath;
        $localPath          =  $this->getClusterDir($clusterFileName);

        $clusterFileSrc     = $clusterFileName;

        if ($localPath)
        {
            $clusterFileSrc = $localPath . DIRECTORY_SEPARATOR . $clusterFileSrc;
        }

        try
        {
            $dir = new Dir($dir . DIRECTORY_SEPARATOR . $localPath);
            $resultFile = $dir->newFile($clusterFileName);
            $tmpFile->move($resultFile);

        } catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }

        return $clusterFileSrc;
    }

    /**
     * Удаление файла
     *
     * @param $clusterFileSrc
     * @return bool
     * @throws Exception
     */
    public function delete($clusterFileUniqSrc)
    {
        $file = new File($this->getRootSrc($clusterFileUniqSrc));
        if ($file->isExist())
        {
            $file->remove();
        }

        return true;
    }


    /**
     * Удаление временной папки
     *
     * @param $clusterFileUniqSrc
     * @return bool|mixed
     */
    public function deleteTmpDir($clusterFileUniqSrc)
    {
        $dir = new Dir($this->rootTmpDir($clusterFileUniqSrc), false);
        if ($dir->isExist())
        {
            $dir->remove();
        }

        return true;
    }

    public function update($clusterFileUniqSrc, $file)
    {}

    /**
     * @param $clusterFileUniqSrc
     * @return string
     */
    public function getAbsoluteUrl($clusterFileUniqSrc)
    {
        if ($this->publicBaseUrlIsAbsolute)
        {
            return $this->getPublicSrc($clusterFileUniqSrc);
        } else
        {
            return \Yii::$app->urlManager->hostInfo . $this->getPublicSrc($clusterFileUniqSrc);
        }
    }

    /**
     * @return bool
     */
    public function existsRootPath()
    {
        if (is_dir($this->rootBasePath))
        {
            return true;
        }

        //Создать папку для файлов если ее нет
        $dir = new Dir($this->rootBasePath);
        if ($dir->make())
        {
            return true;
        }

        return false;
    }
    /**
     * Свободное место на сервере
     * @return float
     */
    public function getFreeSpace()
    {
        if ($this->existsRootPath())
        {
            return (float) disk_free_space($this->rootBasePath);
        }

        return (float) 0;
    }

    /**
     * Всего столько места.
     * @return float
     */
    public function getTotalSpace()
    {
        if ($this->existsRootPath())
        {
            return (float) disk_total_space($this->rootBasePath);
        }

        return (float) 0;
    }
}