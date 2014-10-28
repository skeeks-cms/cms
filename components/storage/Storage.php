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

use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\web\UploadedFile;

use \skeeks\sx\File;
use \skeeks\sx\Dir;



/*interface Storage
{
    public function add($file);
    public function update($storageFileSrc, $file);
    public function delete($storageFileSrc);


}*/

/**
 * Class Storage
 * @package common\components\Storage
 */
class Storage extends Component
{
    public $clusters                = [];

    protected $_clustersLoaded      = [];

    public function init()
    {
        parent::init();

        if ($this->clusters)
        {
            foreach ($this->clusters as $clusterConfig)
            {
                $class = $clusterConfig["class"];
                unset($clusterConfig["class"]);
                $this->_clustersLoaded[$clusterConfig["id"]] = new $class($clusterConfig);
            }
        }
    }


    /**
     *
     * Загрузить файл в хранилище, добавить в базу, вернуть модель StorageFile
     *
     * @param $file
     * @param array $data
     * @param null $clusterId
     * @return StorageFile
     * @throws Exception
     */
    public function upload($file, $data = [], $clusterId = null)
    {
        //Для начала всегда загружаем файл во временную диррикторию
        $tmpdir         = Dir::runtimeTmp();
        $tmpfile        = $tmpdir->newFile();

        if ($file instanceof UploadedFile)
        {
            $extension  = File::object($file->name)->getExtension();
            $tmpfile->setExtension($extension);

            if (!$file->saveAs($tmpfile->getPath()))
            {
                throw new Exception("Файл не загружен во временную диррикторию");
            }
        } else if ($file instanceof File || is_string($file))
        {
            $file       = File::object($file);
            $tmpfile->setExtension($file->getExtension());
            $tmpfile    = $file->move($tmpfile);
        } else
        {
            throw new Exception("Файл должен быть определен как \yii\web\UploadedFile или \skeeks\sx\File или string");
        }

        ;
        $data["type"]       = $tmpfile->getMimeType();
        $data["mime_type"]  = $tmpfile->getMimeType();
        $data["size"]       = $tmpfile->size()->getBytes();
        $data["extension"]  = $tmpfile->getExtension();

        if ($cluster = $this->getCluster($clusterId))
        {
            if ($newFileSrc = $cluster->upload($tmpfile))
            {
                $data = array_merge($data,
                [
                    "src"           => $cluster->getPublicSrc($newFileSrc),
                    "cluster_id"    => $cluster->getId(),
                    "cluster_file"  => $newFileSrc,
                ]);
            }
        }

        $file = new StorageFile($data);
        $file->save(false);

        return $file;
    }



    /**
     * @return array
     */
    public function getClusters()
    {
        return $this->_clustersLoaded;
    }

    /**
     * @param null $clusterId
     * @return Cluster|null
     */
    public function getCluster($clusterId = null)
    {
        if ($clusterId == null)
        {
            foreach ($this->_clustersLoaded as $clusterId => $clusterConfig)
            {
                return $this->_clustersLoaded[$clusterId];
            }
        } else
        {
            return \yii\helpers\ArrayHelper::getValue($this->_clustersLoaded, $clusterId);
        }
    }
}
