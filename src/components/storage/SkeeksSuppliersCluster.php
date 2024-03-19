<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components\storage;

use skeeks\cms\models\StorageFile;
use skeeks\sx\File;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class SkeeksSuppliersCluster extends Cluster
{
    const IMAGE_PREVIEW_MICRO = "micro";
    const IMAGE_PREVIEW_SMALL = "small";
    const IMAGE_PREVIEW_MEDIUM = "medium";
    const IMAGE_PREVIEW_BIG = "big";
    

    public function init()
    {
        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', "SkeekS Suppliers");
        }

        parent::init();
    }

    /**
     * Полный публичный путь до файла.
     * Например /uploads/all/f4/df/sadfsd/sdfsdfsd/asdasd.jpg
     *
     * @param $clusterFileSrc
     * @return string
     */
    public function getPublicUrl(StorageFile $clusterFile)
    {
        return ArrayHelper::getValue($clusterFile, "sx_data.src", "");
    }

    /**
     * @param $clusterFileUniqSrc
     * @return string
     */
    public function getAbsoluteUrl(StorageFile $clusterFile)
    {
        return ArrayHelper::getValue($clusterFile, "sx_data.src", "");
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
        throw new \yii\base\Exception("Не поддерживает загрузку");
    }

    /**
     * Удаление файла
     *
     * @param $clusterFileSrc
     * @return bool
     * @throws Exception
     */
    public function delete(StorageFile $clusterFile)
    {
        return true;
    }


    /**
     * Удаление временной папки
     *
     * @param $clusterFileUniqSrc
     * @return bool|mixed
     */
    public function deleteTmpDir(StorageFile $clusterFile)
    {
        return true;
    }

    public function update($clusterFileUniqSrc, $file)
    {
    }
}