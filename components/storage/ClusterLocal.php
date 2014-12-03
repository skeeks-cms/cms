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
    public $rootBasePath;  //   /var/www/sites/test.ru/frontend/web/uploads/

    /**
     * Добавление
     * @param File $tmpFile
     * @return string
     * @throws Exception
     */
    protected function _upload(File $tmpFile)
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
     * @param $clusterFileSrc
     * @return bool
     * @throws Exception
     */
    protected function _delete($clusterFileSrc)
    {
        $file = new File($this->rootBasePath . DIRECTORY_SEPARATOR . $clusterFileSrc);
        if ($file->isExist())
        {
            $file->remove();
        }

        return true;
    }







}