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
use skeeks\cms\models\ComponentModel;
use Yii;
use yii\base\Component;


use \skeeks\sx\File;
use \skeeks\sx\Dir;

/**
 * Class Cluster
 * @package skeeks\cms\components\storage
 */
abstract class Cluster extends ComponentModel
{
    public $publicBaseUrl; //   http://c1.s.skeeks.com/uploads/

    /**
     * @var integer the level of sub-directories to store uploaded files. Defaults to 1.
     * If the system has huge number of uploaded files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public $directoryLevel = 3;

    /**
     * @return array
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $file
     * @return string $clusterFileUniqSrc
     */
    public function upload(File $file)
    {
        $clusterFileSrc = $this->_upload($file);
        return $clusterFileSrc;
    }

    abstract protected function _upload(File $file);

    public function update($clusterFileUniqSrc, $file)
    {}

    public function delete($clusterFileUniqSrc)
    {
        return $this->_delete($clusterFileUniqSrc);
    }
    abstract protected function _delete($clusterFileUniqSrc);


    /**
     * @param $clusterFileSrc
     * @return string
     */
    public function getPublicSrc($clusterFileSrc)
    {
        return $this->publicBaseUrl . DIRECTORY_SEPARATOR . $clusterFileSrc;
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
        $localDir        =  "";

        if ($this->directoryLevel > 0)
        {
            $count = 0;
            for ($i = 0; $i < $this->directoryLevel; ++$i)
            {
                $count ++;
                if (($prefix = substr($newName, $i + $i, 2)) !== false)
                {
                    if ($count > 1)
                    {
                        $localDir .= DIRECTORY_SEPARATOR;
                    }

                    $localDir .=  $prefix;
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
        $newName =  md5(microtime() . rand(0,100)) ;
        return $originalFileName->getExtension() ? $newName .  "." . $originalFileName->getExtension() : $newName;
    }
}