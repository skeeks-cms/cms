<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components\storage;

use skeeks\sx\Dir;
use skeeks\sx\File;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ClusterLocal extends Cluster
{
    /**
     * @var bool
     */
    public $publicBaseUrlIsAbsolute = false;
    public function init()
    {
        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', "Local storage");
        }

        if (!$this->publicBaseUrl) {
            $this->publicBaseUrl = \Yii::getAlias("@web/uploads/all");
        } else {
            $this->publicBaseUrl = \Yii::getAlias($this->publicBaseUrl);
        }

        if (!$this->rootBasePath) {
            $this->rootBasePath = \Yii::getAlias("@frontend/web/uploads/all");
        } else {
            $this->rootBasePath = \Yii::getAlias($this->rootBasePath);
        }
        
        parent::init();
    }
    /**
     * Добавление файла в кластер
     *
     * @param File $tmpFile
     * @return string
     * @throws Exception
     */
    public function upload(File $tmpFile)
    {
        $clusterFileName = $this->_generateClusterFileName($tmpFile);

        $dir = $this->rootBasePath;
        $localPath = $this->getClusterDir($clusterFileName);

        $clusterFileSrc = $clusterFileName;

        if ($localPath) {
            $clusterFileSrc = $localPath.DIRECTORY_SEPARATOR.$clusterFileSrc;
        }

        try {
            $dir = new Dir($dir.DIRECTORY_SEPARATOR.$localPath);
            $resultFile = $dir->newFile($clusterFileName);
            $tmpFile->move($resultFile);

        } catch (Exception $e) {
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
        if ($file->isExist()) {
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
        if ($dir->isExist()) {
            $dir->remove();
        }

        return true;
    }

    public function update($clusterFileUniqSrc, $file)
    {
    }

    /**
     * @param $clusterFileUniqSrc
     * @return string
     */
    public function getAbsoluteUrl($clusterFileUniqSrc)
    {
        if ($this->publicBaseUrlIsAbsolute) {
            return $this->getPublicSrc($clusterFileUniqSrc);
        } else {
            return \Yii::$app->urlManager->hostInfo.$this->getPublicSrc($clusterFileUniqSrc);
        }
    }
    /**
     * Свободное место на сервере
     * @return float
     */
    public function getFreeSpace()
    {
        if ($this->existsRootPath()) {
            return (float)disk_free_space($this->rootBasePath);
        }

        return (float)0;
    }
    /**
     * @return bool
     */
    public function existsRootPath()
    {
        if (is_dir($this->rootBasePath)) {
            return true;
        }

        //Создать папку для файлов если ее нет
        $dir = new Dir($this->rootBasePath);
        if ($dir->make()) {
            return true;
        }

        return false;
    }
    /**
     * Всего столько места.
     * @return float
     */
    public function getTotalSpace()
    {
        if ($this->existsRootPath()) {
            return (float)disk_total_space($this->rootBasePath);
        }

        return (float)0;
    }
}